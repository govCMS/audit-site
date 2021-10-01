# GovCMS Site Audit

## Drutiny

https://github.com/drutiny/drutiny

Drutiny is a generic Drupal site auditing and optional remediation tool.

### Examples

Here are some examples that how to run it with GovCMS audit cli.

* Check drutiny status

```
vendor/bin/drutiny status
```
* Run a GovCMS D7 site audit

```
vendor/bin/drutiny govcms:audit:run GovCMSD7SiteAudit @govcms7 --format=html --report-filename=govcms-d7.html
```
