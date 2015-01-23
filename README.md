# simple-gh-ci

Simple GitHub continuous integration server.

Actually it's not really CI since it doesn't run after every push (TODO), but it tests pull requests, which is nice.

## Usage

- Create a [GitHub access token](https://help.github.com/articles/creating-an-access-token-for-command-line-use/)
  with OAuth scope `repo:status`.
- Run `make` in the `simple-gh-ci` directory.
- Edit `config.php`:
  - Set `GITHUB_TOKEN` to your access token.
  - Set `RESULTS_URL` to the location of the `results` directory on your web
    server.
- Note that the `builds` and `results` directories are created world-writable
  so that the web server can write to them. If you can, reduce their
  permissions of these directories, while still letting the web server write to
  them.
- For each GitHub repo you want the CI server to build, create a web hook.
  - Set the web hook to receive just "pull request" events.
  - Enter the webhook secret from `config.php`.
  - After adding the web hook, check that the ping event was responded with
    "pong".
  - Try making a pull request to your repo to see if the web hook works.

## References

- https://developer.github.com/guides/building-a-ci-server/
- https://github.com/eclipse/eclipse-webhook

## License

MIT
