# govCMS Site Audit 7.x-2.x
![Travis Build](https://travis-ci.org/govCMS/audit-site.svg?branch=7.x-2.x)

This project is to help site building using the govCMS distribtion audit their
site builds against acceptable use for hosting on the
[govCMS SaaS platform](https://www.govcms.gov.au/how-it-works/compare-saas-and-paas).

This tool is used as a gateway for onboarding and launching sites on the
platform. If you are a site builder, we recommend you use the tool prior to a
 onboard/forklift request to ensure your site meets the acceptable practices
 for hosting on the SaaS platform


## Installation

To audit a site locally, we recommend you download the latest auditor phar for the
version of govCMS you're using from the [releases page](https://github.com/govCMS/audit-site/releases).

```
wget https://github.com/govCMS/audit-site/releases/download/<LATEST_RELEASE>/audit.phar
```

## Usage

To run the audit you'll need to have your site referenceable via a [Drush alias](https://github.com/drush-ops/drush/blob/master/examples/example.aliases.drushrc.php). The Site Audit tool relies on the connection details to the site be setup as a drush alias already.

Before you conduct an audit you should always ensure you're running the latest
release of the auditor. Check the [releases page](https://github.com/govCMS/audit-site/releases) for the latest version.

Conducting the audit is as simple as running this one command with the correct drush alias. If you do not have an alias setup, you can run the auditor from inside root
of a Drupal site using the `@self` drush alias.

```
php audit.phar pre-forklift @self
```

The audit will inform you through commandline output on the results of the audit. At the end of the audit, it will also provide an HTML report that you can open in a browser.
