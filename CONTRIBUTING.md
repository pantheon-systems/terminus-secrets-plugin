## Contributing

Pull requests to this project are welcome and encouraged.

- Please ensure that all code conforms to PSR-2 conventions. `composer cbf` will fix many code style errors.
- Run the unit tests before submitting a pull request (or fix any test failures after submission).
- As new tests to cover new functionality as needed.

## Testing
The preconditions to running tests locally are:

- Run 'composer install' if your local working copy does not have a `vendor` directory yet.
- Install Terminus 1.x, and ensure it is available on your PATH as `terminus`
- Install Terminus 0.x, and ensure it is available on your PATH as `terminus0`
- Export the environment variable TERMINUS_SITE to point at a test site.
- Run `terminus auth:login`

Once that is done, use `composer test` to run the test suite. This will install the test runner, run the tests, and check the sources for PSR-2 compliance. To test only Terminus 1.x, run `composer bats-t1`.

## Adding tests

This project uses BATS, a shell-script testing framework, to manage functional tests for this project. BATS allows you to write tests with simple shell scripts. See the [documentation on writing BATS tests](https://github.com/sstephenson/bats#writing-tests) for more information.
