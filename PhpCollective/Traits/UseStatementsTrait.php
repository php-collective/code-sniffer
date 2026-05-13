<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Traits;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

trait UseStatementsTrait
{
    /**
     * Per-file cache of parsed `use` statements.
     *
     * `getUseStatements()` iterates every token in the file via
     * `foreach ($tokens as ...)` looking for top-level T_USE tokens. The
     * result is stable across calls during a single phpcs pass over a
     * file, but several sniffs call it once per T_FUNCTION
     * (DocBlockThrowsSniff, DocBlockVarSniff, UseWithAliasingSniff). On a
     * large method-heavy controller that turns a per-file O(file_size)
     * walk into per-method O(methods * file_size). Cache the parsed
     * result so each file is parsed at most once per pass.
     *
     * Cache invalidation has to survive phpcbf's fix loop, which re-
     * tokenizes the same file in-place on the same File object after
     * each fix iteration. Token count alone is not sufficient — a fix
     * that renames an alias (`use Foo as Bar;` -> `use Foo as Baz;`)
     * leaves the token count unchanged. We therefore also store the
     * concrete statement strings on the way in and re-verify them
     * against the live tokens before trusting a cache hit. The
     * verification is O(num_use_statements * avg_use_length), typically
     * just a handful of token reads, so it stays cheap.
     *
     * @var array<string, array{count: int, statements: array<string, array<string, mixed>>}>
     */
    private static array $useStatementsCache = [];

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getUseStatements(File $phpcsFile): array
    {
        $tokens = $phpcsFile->getTokens();
        $cacheKey = $phpcsFile->getFilename();
        $tokenCount = count($tokens);
        if (
            isset(self::$useStatementsCache[$cacheKey])
            && self::$useStatementsCache[$cacheKey]['count'] === $tokenCount
            && $this->useStatementsCacheStillValid(self::$useStatementsCache[$cacheKey]['statements'], $tokens)
        ) {
            return self::$useStatementsCache[$cacheKey]['statements'];
        }

        $statements = [];
        foreach ($tokens as $index => $token) {
            if ($token['code'] !== T_USE || $token['level'] > 0) {
                continue;
            }

            $useStatementStartIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $index + 1, null, true);
            if ($useStatementStartIndex === false) {
                continue;
            }

            // Ignore function () use ($foo) {}
            if ($tokens[$useStatementStartIndex]['content'] === '(') {
                continue;
            }

            $semicolonIndex = $phpcsFile->findNext(T_SEMICOLON, $useStatementStartIndex + 1);
            if ($semicolonIndex === false) {
                continue;
            }
            $useStatementEndIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $semicolonIndex - 1, null, true);
            if ($useStatementEndIndex === false) {
                continue;
            }

            $statement = '';
            for ($i = $useStatementStartIndex; $i <= $useStatementEndIndex; $i++) {
                $statement .= $tokens[$i]['content'];
            }

            // Another sniff takes care of that, we just ignore then.
            if ($this->isMultipleUseStatement($statement)) {
                continue;
            }

            $statementParts = preg_split('/\s+as\s+/i', $statement) ?: [];

            if (count($statementParts) === 1) {
                $fullName = $statement;
                $statementParts = explode('\\', $fullName);
                $shortName = end($statementParts);
                $alias = null;
            } else {
                $fullName = $statementParts[0];
                $alias = $statementParts[1];
                $statementParts = explode('\\', $fullName);
                $shortName = end($statementParts);
            }

            $shortName = trim($shortName);
            $fullName = trim($fullName);
            $key = $alias ?: $shortName;

            $statements[$key] = [
                'alias' => $alias,
                'end' => $semicolonIndex,
                'statement' => $statement,
                'fullName' => ltrim($fullName, '\\'),
                'shortName' => $shortName,
                'start' => $index,
                'cacheFingerprint' => $this->buildUseStatementFingerprint($tokens, $index, $semicolonIndex),
            ];
        }

        self::$useStatementsCache[$cacheKey] = [
            'count' => $tokenCount,
            'statements' => $statements,
        ];

        return $statements;
    }

    /**
     * Concatenate the raw content of every token in [$start, $end] inclusive.
     *
     * Used as a fingerprint for cache invalidation: when a phpcbf fix
     * rewrites the inside of a `use` line without changing the total
     * token count of the file, the fingerprint differs and the cached
     * entry is rejected.
     *
     * @param array<int, array<string, mixed>> $tokens
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    private function buildUseStatementFingerprint(array $tokens, int $start, int $end): string
    {
        $fingerprint = '';
        for ($i = $start; $i <= $end; $i++) {
            if (!isset($tokens[$i])) {
                break;
            }
            $fingerprint .= $tokens[$i]['content'];
        }

        return $fingerprint;
    }

    /**
     * Verify a cache entry against the live tokens.
     *
     * For every cached use statement, check that the live tokens at the
     * recorded [start, end] range still produce the same concatenated
     * content (the fingerprint stored when the entry was created). This
     * catches:
     *   - structural edits that shifted positions without changing
     *     overall token count (cached T_USE no longer at `start`);
     *   - in-place rewrites that preserve token count, e.g. renaming
     *     an imported alias to a different identifier of the same shape;
     *   - any whitespace/comment edit inside the use statement.
     *
     * Cost per call is O(num_use_statements * avg_use_length), typically
     * just a handful of token reads.
     *
     * @param array<string, array<string, mixed>> $statements
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    private function useStatementsCacheStillValid(array $statements, array $tokens): bool
    {
        foreach ($statements as $statement) {
            $start = $statement['start'];
            if (!isset($tokens[$start]) || $tokens[$start]['code'] !== T_USE) {
                return false;
            }

            $live = $this->buildUseStatementFingerprint($tokens, $start, $statement['end']);
            if ($live !== $statement['cacheFingerprint']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $statementContent
     *
     * @return bool
     */
    protected function isMultipleUseStatement(string $statementContent): bool
    {
        if (str_contains($statementContent, ',')) {
            return true;
        }

        return false;
    }
}
