# simple-gh-ci

Simple GitHub continuous integration server.

## Usage

- [Create a GitHub access token](https://help.github.com/articles/creating-an-access-token-for-command-line-use/)
  with OAuth scope `repo:status`.
- Run `make` in the `simple-gh-ci` directory.
- Put your access token in `config.php`.
- Not that the `builds` and `results` directories are created world-writable so
  that the web server can write to them. If you can, reduce their permissions
  of these directories, while still letting the web server write to them.
- For each GitHub repo you want the CI server to build, create a web hook.
- Set the web hook to receive just "pull request" events.
- Enter the webhook secret from `config.php`
- Try making a pull request to your repo to see if the web hook works.

## References

- https://developer.github.com/guides/building-a-ci-server/
- https://github.com/eclipse/eclipse-webhook

## License

MIT
