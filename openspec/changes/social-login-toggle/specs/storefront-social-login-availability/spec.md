## Purpose

Memberikan kontrol admin yang konsisten untuk mengatur apakah pelanggan dapat memulai login melalui Google atau GitHub pada storefront, sekaligus memastikan endpoint OAuth mengikuti konfigurasi tersebut.

## ADDED Requirements

### Requirement: Admin can configure social login availability

The system SHALL provide a global social-login availability setting in the Website settings Access section. The setting SHALL default to enabled for existing installations and SHALL persist when the administrator saves the settings.

#### Scenario: Social login setting defaults to enabled

- **WHEN** an installation receives the new setting without a saved value
- **THEN** social login is treated as enabled

#### Scenario: Administrator disables social login

- **WHEN** an authorized administrator turns off the Login Sosial toggle and saves Website settings
- **THEN** the saved social-login availability becomes disabled

#### Scenario: Administrator enables social login

- **WHEN** an authorized administrator turns on the Login Sosial toggle and saves Website settings
- **THEN** the saved social-login availability becomes enabled

### Requirement: Storefront reflects social login availability

The login page SHALL display the Google and GitHub social-login controls, including their separator, only when social login is enabled. Existing private-store restrictions SHALL continue to suppress social-login controls regardless of this setting.

#### Scenario: Enabled social login is visible on a public store

- **WHEN** social login is enabled and the store is not private
- **THEN** the login page displays both Google and GitHub login controls

#### Scenario: Disabled social login is hidden on the login page

- **WHEN** social login is disabled
- **THEN** the login page does not display the social-login controls or their separator

#### Scenario: Private store restriction remains authoritative

- **WHEN** social login is enabled but the store is private
- **THEN** the login page does not display social-login controls

### Requirement: Disabled customer registration is enforced

When customer registration is disabled in Website settings, the system SHALL prevent visitors from opening or submitting the customer registration flow. Existing private-store behavior SHALL remain unchanged.

#### Scenario: Registration page is requested while registration is disabled

- **WHEN** an unauthenticated visitor requests the registration page while customer registration is disabled
- **THEN** the visitor is redirected to the registration-closed page and no registration form is rendered

#### Scenario: Registration is submitted while registration is disabled

- **WHEN** a visitor submits registration data while customer registration is disabled
- **THEN** the request is rejected and no customer account is created

### Requirement: Disabled social login endpoints reject OAuth access

The system SHALL reject direct requests to social-login redirect and callback endpoints when social login is disabled, without initiating or completing an OAuth flow.

#### Scenario: Redirect endpoint is requested while disabled

- **WHEN** an unauthenticated visitor requests a supported provider redirect while social login is disabled
- **THEN** the request is rejected and no provider authorization redirect is initiated

#### Scenario: Callback endpoint is requested while disabled

- **WHEN** an OAuth callback is requested while social login is disabled
- **THEN** the request is rejected and no customer is authenticated or created through social login

#### Scenario: Enabled endpoint continues the existing flow

- **WHEN** social login is enabled and a supported provider redirect or callback is requested
- **THEN** the existing social-login flow remains available

### Requirement: Social-login configuration text is localized

The admin toggle label, helper text, and any user-facing social-login availability text SHALL be available in Indonesian and English.

#### Scenario: Indonesian locale displays localized configuration text

- **WHEN** an administrator views Website settings using Indonesian
- **THEN** the Login Sosial label and helper text are displayed in Indonesian

#### Scenario: English locale displays localized configuration text

- **WHEN** an administrator views Website settings using English
- **THEN** the social-login label and helper text are displayed in English
