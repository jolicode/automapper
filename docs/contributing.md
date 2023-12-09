# Contributing

## Releasing

Whenever you're doing a release you need to do some updates in order for the project to keep history on what was done
and some other stuff.

### Changelog

First you'll need to update the CHANGELOG file (`./CHANGELOG.md`), take everything under the `Unreleased` section and
create a new section for your new tag with the today's date.

### Version

When a new version is tagged, you have to update the version constants within the `AutoMapper/AutoMapper` so 
transformers can be updated with last AutoMapper version.
