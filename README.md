# Repeat Survey Link
Access to the next instance of a repeating instrument's survey from any other instrument/form/survey.

**This module is only working with the first event id. A full multiple event functionality is not implemented.**

## Description
This module adds the possibility to define a "Repeat Survey Link" that can be accessed from anywhere. Currently, the REDCap Smart Variable `[next-instance]` does only work within the same instrument context and therefore cannot be used to generate a survey link from another instrument for its next instance.

After saving the module configuration (see next section), the module logic will be triggered once for all relevant records and then after each save on the specific record itself. 
Hooks beeing used are:
- redcap_module_save_configuration
- redcap_save_record

## Setup / Usage

Install the module from REDCap module repository and enable over Control Center.
Before configuring the module it is recommended to fullfil necessary requirements and limitations, as listed below.

After enabling the module for a project go to module configuration and add a "Repeat Survey Link":
- Define the repeating instrument that you would like to generate the survey link to its next instance
- Select the "helper variable" that you would like to save the generated repeat-survey-link into
- Save configuration (*This triggers a expensive query over all records, use wisely*)

Repeat this process for any other repeating instrument by adding a new "Repeat Survey Link".

**Necessary Requirements/Limitations**

Project:
- Enabled: Use surveys in this project
- Enabled: Repeatable instruments
- Multiple Arms not supported: It is recommended to use this module only in projects with a single arm.

Repeating Instrument:
- Selected as Repeating Instrument within Project Setup (obviously)
- Enabled as Survey within Designer

Helper Variable:
- should be a field of a **non-repeating instrument**.
- should be of type text.
- should have a @READONLY Action Tag.
- should be named in a way that its association to the repeating instrument and the module is clear, e.g.:
    helper_rsl_instrumentName

Database:
- DB Placeholder limit at least 65000.

## Roadmap
- handle module disable (delete generated links?)
- helper variable validation (type=text, readonly, etc?)

## Changelog

Version | Description
------- | --------------------
v1.0.0  | Initial release.
v1.0.1  | DB security enhancement.
v1.0.2  | Use parametrized queries.
v1.0.3  | Minor Fix.
