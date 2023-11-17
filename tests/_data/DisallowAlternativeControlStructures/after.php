<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    public function test(): void {
        foreach ($usecases as $usecase) {
            $usecase->setDirty('name', true);
            $usecasesTable->save($usecase);
        }

        if ($usecase) {
            $usecasesTable->save();
        }

        while ($x < 0) {
            $usecasesTable->save();
            $x--;
        }

        switch ($foo) {
            case 1:
                $usecasesTable->save();
        }

        for ($i < 0; $i--) {
            $usecasesTable->save();
        }
    }
}
