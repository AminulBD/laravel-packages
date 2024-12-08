<?php

namespace AminulBD\Package\Laravel;

interface PackageActivationHandler
{
    /**
     * @return string[]
     */
    public function enabled(): array;
}
