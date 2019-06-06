<?xml version="1.0" encoding="UTF-8"?>
<phpunit colors="true" processIsolation="false" stopOnFailure="false" bootstrap="../../vendor/originphp/framework/src/bootstrap.php" backupGlobals="true">
  <testsuites>
    <testsuite name="Plugin Test Suite">
      <directory>./tests/TestCase/</directory>
    </testsuite>
  </testsuites>
  <php>
    <const name="PHPUNIT" value="true"/>
    <env name="ORIGIN_ENV" value="test"/>
  </php>
  <listeners>
    <listener class="Origin\TestSuite\OriginTestListener" file="../../vendor/originphp/framework/src/TestSuite/OriginTestListener.php"></listener>
  </listeners>
  <filter>
    <whitelist>
      <directory suffix=".php">./src/</directory>
    </whitelist>
  </filter>
</phpunit>