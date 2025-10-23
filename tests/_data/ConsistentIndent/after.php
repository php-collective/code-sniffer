<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    public function orphanedIndentExample($params): array
    {
        if (!isset($params['url']['unresolved'])) {
            $unresolved = 'false';
            $unresolvedSet = 'false';
        } else {
            $unresolved = $params['url']['unresolved'];
        }
        if (!isset($params['url']['filter'])) {
            $filter1 = 'true';
        } else {
            $filter1 = $params['url']['filter'];
        }

        return [$unresolved, $filter1];
    }

    public function anotherExample(): void
    {
        $value = 1;

        $anotherValue = 2;

        echo $value;
    }

    public function validContinuation(): string
    {
        $result = 'foo' .
            'bar' .
            'baz';

        return $result;
    }

    public function validMultilineArray(): array
    {
        return [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
    }

    public function closureShouldNotBeFlagged(): void
    {
        $validator = [];
        $validator[] = [
            'rule' => function ($value, $context) {
                // This comment inside closure is valid
                if ($value === '') {
                    return true;
                }

                return false;
            },
            'message' => 'Test message',
        ];
    }

    public function switchCaseShouldNotBeFlagged($type): void
    {
        switch ($type) {
            case 'foo':
                $result = 'Foo result';

                break;
            case 'bar':
                $result = 'Bar result';

                break;
            default:
                $result = 'Default';

                break;
        }
    }

    public function ternaryOperatorShouldNotBeFlagged($condition): string
    {
        $result = $condition
            ? 'true value'
            : 'false value';

        return $result;
    }

    public function methodChainingWithComments(): void
    {
        $query = $this->find()
            ->where(['status' => 'active'])

        // This comment between chains should not be flagged
            ->orderBy(['created' => 'DESC'])

        // Another comment
            ->limit(10);
    }

    public function nullCoalesceShouldNotBeFlagged($params): ?int
    {
        return $this->AuthUser->get('User.home_id')
            ?? $params['home_id']
            ?? null;
    }
}
