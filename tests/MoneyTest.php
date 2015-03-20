<?php

class MoneyTest
{
    public function run()
    {
        $money = new Money('10.00');
        is('10.00', (string) $money);

        $money = new Money('10.45');
        $finalMoney = $money->add(new Money('21.55'));
        is('32.00', (string) $finalMoney);

        $money = new Money('54.46');
        $finalMoney = $money->multiply(100);
        is('5446.00', (string) $finalMoney);

        //
        // This forces to use bc_*() functions.
        // Of course we probably don't need such large numbers for Money,
        // but arbitrary precision reflects on the single hundreths.
        //
        $money = new Money('54.46');
        $finalMoney = $money->multiply('100000000000000000');
        is('5446000000000000000.00', (string) $finalMoney);
    }
}