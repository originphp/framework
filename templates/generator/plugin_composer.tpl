{
    "name": "your-name/%underscored%",
    "description": "%namespace% plugin for OriginPHP",
    "type": "origin-plugin",
    "license": "MIT",
    "require": {
        "originphp/framework": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8"
    },
    "autoload": {
        "psr-4": {
            "%namespace%\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "%namespace%\\Test\\": "tests/",
            "Origin\\Test\\": "vendor/originphp/framework/tests/"
        }
    }
}