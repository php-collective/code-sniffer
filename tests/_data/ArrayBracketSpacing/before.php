<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    public function test(): void
    {
        // Extra blank lines before closing bracket
        $array1 = [
            'item1',
            'item2',
            'item3',

        ];

        // Multiple extra blank lines
        $array2 = [
            'key1' => 'value1',
            'key2' => 'value2',


        ];

        // Nested array with extra blank lines
        $array3 = [
            'contain' => [
                'x', 'y', 'z',
                'Brands', 'Styles', 'Samples',
                '12', '34',

            ],
        ];

        // This is fine - single line array
        $array4 = ['a', 'b', 'c'];

        // This is fine - no extra blank lines
        $array5 = [
            'item1',
            'item2',
            'item3',
        ];

        // This is fine - one blank line is acceptable
        $array6 = [
            'item1',
            'item2',

        ];

        // Array with comment as last element followed by blank lines
        // This should report an error but NOT be auto-fixable
        $array7 = [
            'key' => 'value',
            //'conditions' => array('ConversationUsers.status <'=>ConversationUser::STATUS_REMOVED),
            //'group' => array('ConversationUser.conversation_id HAVING SUM(...)'), //HAVING COUNT(*) = '.count($users).'

        ];
    }
}
