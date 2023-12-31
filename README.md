![PHPStan](https://github.com/visible-bits/t3zip/actions/workflows/phpstan.yml/badge.svg)
![PHPCodeSniffer](https://github.com/visible-bits/t3zip/actions/workflows/phpcs.yml/badge.svg)
![PHPRector](https://github.com/visible-bits/t3zip/actions/workflows/rector.yml/badge.svg)

# TYPO3 Extension `t3zip`
Unzip (and in the future also zip) support for TYPO3.

Add issues or explore the project on [GitHub](https://github.com/visible-bits/t3zip).

## Requirements and caveats
This extension was developed an Linux and MacOs (using docker). It was never tested on a Windows system and therefore it is possible that it does not work as expected when used on a operating system other than Mac or Linux.

On possible point of failure is the use of the /tmp folder to store temporary files during the extraction of the .zip file.

## Installation
Either install via composer (recommended) or from TER
- Install the extension with composer ```composer req visible-bits/t3zip```
- Install the extension from [TYPO3 TER](https://extensions.typo3.org/extension/t3zip/)

## Configuration

### Add extracted files to TYPO3 index
Default: If a .zip file was extraxted, all files inside the extracted folder are added to the TYPO3 index and entries in the sys_file table are made. This can be disabled in the extension settings inside the TYPO3 install tool.

### Translation
All localized strings can be translated via the default TYPO3 process (see EXT:t3zip/Resources/Private/Language/locallang.xlf)