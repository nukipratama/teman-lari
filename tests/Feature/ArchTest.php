<?php

declare(strict_types=1);

arch('app namespace stays free of debug helpers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

// Notes:
// - 1:1 class↔test enforcement: tracked separately (CI grep, see ci.yml).
//   pest-plugin-arch v4 doesn't ship a stable `toHaveTests()` helper.
// - strict_types enforcement: handled by Rector's `DeclareStrictTypesRector`
//   (config in `rector.php`) — `arch()->toUseStrictTypes()` currently throws
//   on Override-attributed framework providers.
