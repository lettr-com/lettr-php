# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [2.4.0] - 2026-06-01

### Added
- Optional `$userAgentSuffix` parameter on `Lettr::client()` and `Client::__construct()` — appended to the outgoing `User-Agent` header (e.g. `lettr-php/2.4.0 lettr-laravel/2.2.0`) so wrapping packages can identify themselves. Control characters (incl. CR/LF) are stripped from the suffix to prevent header injection.

## [2.3.0] - 2026-05-28

### Added
- `Dto\Campaign\CampaignDetail` — subclass of `CampaignSummary` that adds `$htmlContent` (the rendered email body). Returned by `CampaignService::get()`.

### Changed
- `CampaignService::get()` return type narrowed from `CampaignSummary` to `CampaignDetail`. Substitutable everywhere a `CampaignSummary` was expected, so existing code continues to compile.
- `htmlContent` is no longer a field on `CampaignSummary`. The API only returns `html_content` from `GET /campaigns/{id}`, so exposing it on the summary type was misleading — `list()`, `send()`, `schedule()`, and `unschedule()` callers no longer see a phantom `?string $htmlContent` that would always be `null`.

### Fixed
- `send()`, `schedule()`, and `unschedule()` no longer leak `htmlContent` when the API omits `data` from the action envelope and the SDK refetches the campaign. The refetched `CampaignDetail` is now downcast to a `CampaignSummary` before being returned, matching the action endpoints' contract (which does not include `html_content`).

## [2.2.0] - 2026-05-28

### Added
- **Campaigns module** — 6 endpoints exposed under `$lettr->campaigns()`:
  - `list(?ListCampaignsFilter)` — `GET /campaigns`, paginated, with embedded engagement stats; filter by `status` (`CampaignStatus`).
  - `get(string $id)` — `GET /campaigns/{id}`, returns a `CampaignSummary` with `$campaign->htmlContent` populated (the rendered email body). List responses use the same `CampaignSummary` shape but leave `htmlContent` `null`.
  - `events(string $id, ?ListCampaignEventsFilter)` — `GET /campaigns/{id}/events`, cursor-paginated engagement events. Filter accepts the existing `EventType` enum and tolerates `null`/empty cursors (omitted from the query). Pass `nextCursor` from the previous response or `null` on the first call.
  - `send(string $id)` — `POST /campaigns/{id}/send`, dispatches a draft campaign now.
  - `schedule(string $id, DateTimeInterface|string $scheduledAt)` — `POST /campaigns/{id}/schedule`. Schedules a draft for future delivery **or reschedules an already-scheduled campaign**. A `DateTimeInterface` is formatted to ISO-8601 with offset; strings pass through unchanged.
  - `unschedule(string $id)` — `POST /campaigns/{id}/unschedule`, returns a scheduled campaign to draft.
  - The three action methods return a non-null `CampaignSummary`. If the API omits the campaign payload from the action response, the SDK transparently refetches via `get($id)` so callers never see `null`.
- New DTOs under `Dto\Campaign\` (`CampaignSummary`, `CampaignStats`, `CampaignEvent`, `ListCampaignsFilter`, `ListCampaignEventsFilter`).
- New `CampaignCollection` (typed `findById()` only; shared boilerplate lives on the new abstract `Lettr\Collections\Collection` base), plus the `ListCampaignsResponse` and `ListCampaignEventsResponse` wrappers.
- New `Lettr\Responses\Pagination` shared base — `AudiencePagination`, `ProjectPagination`, and `TemplatePagination` are now thin subclasses of it, eliminating the per-resource duplication and providing the same class for the campaigns list response.
- New enum: `CampaignStatus` (`draft`, `scheduled`, `preparing`, `in_review`, `sending`, `sent`, `failed`). The existing `Enums\EventType` is reused for campaign events.
- `$campaign->status` is typed `CampaignStatus|string` and `$event->eventType` is typed `EventType|string` — unknown values from a server-side enum extension are preserved as raw strings instead of throwing `ValueError`.
- `TransporterContract::postExpectingEnvelope(string $uri, ?array $data = null): array` — returns the full decoded response body without unwrapping the `data` envelope, and accepts `null` to omit the request body entirely (no `[]` JSON body for endpoints that take no input). Used by campaign action methods.

## [2.1.0] - 2026-05-22

### Added
- **Audience module** — 33 new endpoints across 5 sub-resources (lists, contacts, topics, properties, segments) exposed under `$lettr->audience`:
  - `$lettr->audience->lists()` — list, get, create, update (PATCH), delete, bulkDelete
  - `$lettr->audience->contacts()` — list, get, create, update (PATCH), delete, bulkCreate, bulkAttachLists, bulkDetachLists, attachList, detachList, subscribeTopic, unsubscribeTopic. `attachList()` and `subscribeTopic()` return `bool` (`true` = newly attached / 201; `false` = already existed / 200).
  - `$lettr->audience->topics()`, `$lettr->audience->properties()`, `$lettr->audience->segments()` — standard CRUD
- New DTOs under `Dto\Audience\` (entities, write data, filters, bulk results, `DoubleOptInConfig`, `SegmentConditionsInput`)
- New collections (`AudienceListCollection`, `AudienceContactCollection`, `AudienceTopicCollection`, `AudiencePropertyCollection`, `AudienceSegmentCollection`) and list-response wrappers
- New enums: `AudienceContactStatus`, `AudienceTopicDefaultSubscription`, `AudienceTopicVisibility`, `AudiencePropertyType`, `SegmentOperator`
- New `ContactProperties` value object (used on `AudienceContact->properties`)
- `TransporterContract::patch()` and `TransporterContract::deleteWithBody()` for PATCH and DELETE-with-body endpoints
- `TransporterContract::lastStatusCode()` exposes the HTTP status from the last successful response (used by attach/subscribe to distinguish 200/201)
- `AudienceTopicDefaultSubscription::subscribesNewContactsByDefault()` helper. Returns `true` for `OptOut` (auto-subscribe) and `false` for `OptIn` (must opt in). Use this in preference to `isOptIn()` when you care about the resulting subscription state rather than the case identity.

### Fixed
- `ContactProperties` (and every `Collection` under `src/Collections/`) now implement `JsonSerializable` so `json_encode()` preserves property keys (key→value object for `ContactProperties`) and item order (positional JSON array for collections). Previously, a generic dumper that walked them via `IteratorAggregate` could drop string keys (e.g. emitting `["SDK"]` instead of `{"first_name":"SDK"}`).

## [2.0.0] - 2026-04-23

### Breaking changes
- `Dto\Domain\Domain` drops `returnPathStatus` and `verifiedAt`; `Dto\Domain\DomainDetail` drops `verifiedAt` (API never emitted these fields)
- `Dto\Domain\DomainVerification::$ownershipVerified` retyped from `?bool` to `?string` (API returns `"true"` quoted)
- `Dto\Template\CreateTemplateData::$slug` removed — the API now server-generates slugs
- `Dto\Webhook\Webhook::$eventTypes` is now `?WebhookEventTypeCollection` (nullable). `null` signals the webhook is subscribed to every event; any code iterating the collection without a null check must guard with `listensToAllEvents()`
- `Dto\Webhook\UpdateWebhookData::$target` renamed to `$url` to match the API's field name on `PUT /webhooks/{id}`
- `EmailService::sendTemplate()` parameters reordered — `$templateSlug` now comes before `$subject`, and `$subject` is optional (defaults to `null`). Call sites using named arguments are unaffected; positional callers must reorder the 3rd–4th arguments
- `Webhook::from()` previously parsed `event_types` through the unprefixed `EventType` enum, which would throw on any real webhook response (spec uses `message.delivery` etc.). Webhooks now use the new `WebhookEventType` enum

### Added
- **Email list & events endpoints**
  - `EmailService::list(?ListEmailsFilter)` for `GET /emails` with cursor pagination
  - `EmailService::events(?ListEmailEventsFilter)` for `GET /emails/events`
  - `EmailService::find(string|RequestId)` for `GET /emails/{requestId}`
  - New DTOs: `ListEmailsFilter`, `ListEmailEventsFilter`, `SentEmail`, `EmailEvent`, `CursorPagination`
  - New responses: `ListEmailsResponse`, `ListEmailEventsResponse`
- **Scheduled emails**
  - `EmailService::schedule(SendEmailData|EmailBuilder)` for `POST /emails/scheduled`
  - `EmailService::getScheduled(string)` and `EmailService::cancelScheduled(string)`
  - `TransmissionDetail` DTO (also used by `find()` — per spec, scheduled and detail endpoints share the same shape) and `TransmissionState` enum (`submitted`, `generating`, `scheduled`, `delivered`, `bounced`, `failed`, `unknown`)
  - `EmailBuilder::scheduledAt()` and `ampHtml()` helpers
- **Template management**
  - `TemplateService::update(string, UpdateTemplateData)` for `PUT /templates/{slug}`
  - `TemplateService::getHtml(int, string)` for `GET /templates/html`
  - `TemplateService::getMergeTags(string, ?int, ?int)` for `GET /templates/{slug}/merge-tags`
  - New DTOs: `UpdateTemplateData`, `UpdatedTemplate`, `GetTemplateHtmlResponse`
  - `MergeTag` gains an optional `name` field
- **Webhook CRUD**
  - `WebhookService` now supports `list`, `get`, `create`, `update`, `delete`
  - New DTOs: `CreateWebhookData`, `UpdateWebhookData` (the update payload uses `url`, matching the API)
  - `Webhook::listensToAllEvents()` helper (returns `true` when the webhook subscribes to every event, i.e. `eventTypes === null`)
  - New enum `WebhookEventsMode` (`all`, `selected`)
  - New enum `WebhookEventType` with 22 namespaced cases (`message.*`, `engagement.*`, `generation.*`, `unsubscribe.*`, `relay.*`) — distinct from `EventType` used for email-event filtering
  - New `WebhookEventTypeCollection`
- **Domain list & detail**
  - `Domain` DTO gains `statusLabel`, `updatedAt`, and `cnameStatus`; drops `returnPathStatus` (was always `Unverified` — never returned by API) and `verifiedAt` (never returned)
  - `DomainDetail` gains `statusLabel`, `updatedAt`, `spfStatus`, `isPrimaryDomain`, and `dnsProvider` (new `DnsProvider` DTO with `provider`, `providerLabel`, `nameservers`, `error`); drops `verifiedAt`
- **Domain verification** — `DomainVerification` DTO now surfaces `dmarc_status`, `spf_status`, `is_primary_domain`, and the `DmarcVerification` / `SpfVerification` sub-objects. Drops the undocumented `dkim/cname/dmarc/spf_warning_level` fields (API never emitted them — `from()` previously threw `Undefined array key` on real responses). `ownershipVerified` retyped from `?bool` to `?string` to match the actual response ("true" quoted)
- **Email events** — `EmailEvent` DTO gains every `CommonEventProperties` field (`campaign_id`, `template_id`, `template_version`, `ip_pool`, `msg_from`, `rcpt_type`, `rcpt_tags`, `amp_enabled`, `delv_method`, `recv_method`, `routing_domain`, `scheduled_time`, `ab_test_id`, `ab_test_version`, `rcpt_meta`) plus per-event-type extras (`outbound_tls`, `device_token`, `fbtype`, `report_by`, `report_to`, `remote_addr`, `initial_pixel`). New `UserAgentParsed` and `GeoIp` DTOs replace raw arrays
- **Auth** — `HealthService::authCheck()` against `GET /auth/check`
- **Transport** — `TransporterContract::getWithQuery()` for query-string GETs and `TransporterContract::put()` for PUT requests (used by template and webhook updates)
- **Error codes** — `ErrorCode` enum expanded with `unconfigured_domain`, `send_error`, `retrieval_error`, `transmission_failed`, `resource_already_exists`, `not_found`, `template_not_found`, `schedule_cancellation_failed`
- **AMP events** — `EventType` enum now includes `amp_click`, `amp_open`, `amp_initial_open`

### Fixed
- `Webhook::from()` previously parsed `event_types` through the unprefixed `EventType` enum, which would throw on any real webhook response (spec uses `message.delivery` etc.). Webhooks now use `WebhookEventType`.

## [1.3.0] - 2026-03-21

### Added
- Custom email headers support via `CustomHeaders` DTO, `EmailBuilder::headers()` and `EmailBuilder::addHeader()` methods
- Validation for max 10 headers and max 998 characters per header value

### Fixed
- Changed default `transactional` option in `EmailBuilder` from `false` to `true` to match the API default

## [0.1.3] - 2025-01-23

### Fixed

- Fixed API response parsing to unwrap `data` envelope

## [0.1.2] - 2025-01-23

### Fixed

- Fixed `from` field format to use `from` (email) and `from_name` (name) as separate fields

## [0.1.1] - 2025-01-23

### Fixed

- Fixed API base URL missing trailing slash causing incorrect endpoint URLs

## [0.1.0] - 2025-01-23

### Added

- Initial release of the Lettr PHP SDK
- **Email Service**
  - Send emails with HTML, plain text, or templates
  - Fluent `EmailBuilder` for composing emails
  - Support for attachments, CC, BCC, and reply-to
  - Email tracking and event history
  - List emails with filtering and pagination
- **Domain Service**
  - Create and manage sending domains
  - DNS verification status
  - DKIM configuration
- **Webhook Service**
  - Create, update, and delete webhooks
  - Support for multiple event types (delivered, bounced, opened, clicked, etc.)
  - Webhook authentication (Basic, Bearer)
- **Type-safe DTOs and Value Objects**
  - Strongly typed request/response objects
  - Value objects for EmailAddress, MessageId, DomainName, etc.
- **Exception Handling**
  - Specific exceptions for API errors (ValidationException, NotFoundException, etc.)
  - Error codes enum for programmatic error handling
- **PHP 8.4+ Support**
  - Modern PHP with strict types
  - Native enums for status values
