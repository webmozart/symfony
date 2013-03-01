<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Icu\IcuData;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\ResourceBundle\Transformer\BundleTransformer;
use Symfony\Component\Intl\ResourceBundle\Transformer\Rule\CurrencyBundleTransformationRule;
use Symfony\Component\Intl\ResourceBundle\Transformer\Rule\LanguageBundleTransformationRule;
use Symfony\Component\Intl\ResourceBundle\Transformer\Rule\LocaleBundleTransformationRule;
use Symfony\Component\Intl\ResourceBundle\Transformer\Rule\RegionBundleTransformationRule;
use Symfony\Component\Intl\ResourceBundle\Transformer\StubbingContext;

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/autoload.php';

if (1 !== $GLOBALS['argc']) {
    bailout(<<<MESSAGE
Usage: php update-stubs.php

Creates resource bundle stubs from the resource bundles in the Icu component.

For running this script, the intl extension must be loaded and all vendors
must have been installed through composer:

    composer install --dev

MESSAGE
    );
}

echo LINE;
echo centered("ICU Resource Bundle Stub Creation") . "\n";
echo LINE;

if (!Intl::isExtensionLoaded()) {
    bailout('The intl extension for PHP is not installed.');
}

if (!class_exists('\Symfony\Component\Icu\IcuData')) {
    bailout('You must run "composer update --dev" before running this script.');
}

$shortIcuVersionInPhp = strip_minor_versions(Intl::getIcuVersion());
$shortIcuVersionInIntlComponent = strip_minor_versions(Intl::getStubIcuVersion());
$shortIcuVersionInIcuComponent = strip_minor_versions(IcuData::getVersion());

if ($shortIcuVersionInPhp !== $shortIcuVersionInIcuComponent) {
    bailout("The ICU version of the component ($shortIcuVersionInIcuComponent) does not match the ICU version in the intl extension ($shortIcuVersionInPhp).");
}

if ($shortIcuVersionInIntlComponent !== $shortIcuVersionInIcuComponent) {
    bailout("The ICU version of the component ($shortIcuVersionInIcuComponent) does not match the ICU version of the stub classes in the Intl component ($shortIcuVersionInIntlComponent).");
}

$icuVersionInIcuComponent = IcuData::getVersion();

echo "Compiling stubs for ICU version $icuVersionInIcuComponent.";

echo "Preparing stub creation...\n";

$context = new StubbingContext(
    IcuData::getResourceDirectory(),
    realpath(__DIR__ . '/../data'),
    new Filesystem(),
    $icuVersionInIcuComponent
);

$transformer = new BundleTransformer();
$transformer->addRule(new LanguageBundleTransformationRule());
$transformer->addRule(new RegionBundleTransformationRule());
$transformer->addRule(new CurrencyBundleTransformationRule());
$transformer->addRule(new LocaleBundleTransformationRule());

echo "Starting stub creation...\n";

$transformer->createStubs($context);

echo "Stub creation complete.\n";

$versionFile = $context->getStubDir() . '/version.txt';

file_put_contents($versionFile, "$icuVersionInIcuComponent\n");

echo "Wrote $versionFile.\n";

echo "Done.\n";
