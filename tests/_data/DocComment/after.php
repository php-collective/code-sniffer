<?php

declare(strict_types=1);

namespace PhpCollective;

class DocCommentExample
{
    /**
     * Close tag shares a line. 
     */
    public function closeTagSharesLine(): void
    {
    }

    /**
     * Short description starts too late.
     */
    public function shortDescriptionStartsTooLate(): void
    {
    }

    /**
     * Extra blank line before the close tag.
     */
    public function extraBlankLineAtEnd(): void
    {
    }

    /**
     * Tags are too close.
     *
     * @return void
     */
    public function tagsNeedSpacing(): void
    {
    }

    /**
     * Valid comment.
     *
     * @return void
     */
    public function validComment(): void
    {
    }
}
