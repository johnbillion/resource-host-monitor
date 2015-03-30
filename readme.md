[![License](https://img.shields.io/badge/license-GPL_v2%2B-blue.svg?style=flat-square)](http://opensource.org/licenses/GPL-2.0)

# Resource Host Monitor #

Resource Host Monitor is a plugin for WordPress which records the host names of any third party resources (for example, JavaScript files, CSS files, images, and fonts) on your site, and then performs analysis on the hosts for quality control and curiosity purposes.

Currently, RHM performs the following analysis on the hosts that it records:

 * HTTPS availability: Is the host available over HTTPS?
 * [SSL Server Test](https://www.ssllabs.com/ssltest/) report: Uses the SSL Labs API to report back on the host's SSL/TLS implementation.

Analysis is queued and performed asynchronously, and in the case of the SSL Labs report, performed with rate limiting in place to keep everyone happy.

RHM began life as an idea for an enhancement to the [HTTPS Mixed Content Detector plugin](https://github.com/tollmanz/wordpress-https-mixed-content-detector/). Over time, the two plugins may end up interfacing with each other if they're both installed on the same site.

# Contributing #

Code contributions are very welcome, as are bug reports in the form of GitHub issues. Development happens in the `develop` branch, and any pull requests should be made to that branch please.

# License: GPLv2 #

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
