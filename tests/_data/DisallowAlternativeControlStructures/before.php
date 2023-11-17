<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    public function test(): void {
        foreach ($usecases as $usecase) :
            $usecase->setDirty('name', true);
            $usecasesTable->save($usecase);
        endforeach;

        if ($usecase) :
            $usecasesTable->save();
        endif;

        while ($x < 0) :
            $usecasesTable->save();
            $x--;
        endwhile;

        switch ($foo) :
            case 1:
                $usecasesTable->save();
        endswitch;

        for ($i < 0; $i--) :
            $usecasesTable->save();
        endfor;
    }
}
