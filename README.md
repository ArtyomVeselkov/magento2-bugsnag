# magento2-bugsnag
## About
Bugsnag featured integration for Magento 2 with early start point of handling exceptions.

## TODO
- [ ] Plugin for cron.
- [ ] Auto-deploy include file (for file `setup/config/autoload/bugsnag.local.php`).
- [ ] Queue for messages.
- [ ] Encryption for stored records.
- [ ] Send build info only once (cache).

## Notes
- Even being disabled via `bin/magento module:disable`, but with `active` flag set to `true` in env.php, extension
  will be working.
