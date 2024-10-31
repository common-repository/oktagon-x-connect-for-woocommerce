<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Package
{
    private $packageItems = [];

    public function addPackageItem(
        PackageItem $packageItem
    ) {
        $this->packageItems[] =
            $packageItem;
    }

    public function getPackageItems()
    {
        foreach ($this->packageItems as $packageItem) {
            yield $packageItem;
        }
    }
}
