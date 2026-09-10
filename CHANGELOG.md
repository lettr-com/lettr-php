# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [2.7.0] - 2026-09-09

Two additions: knowing when an imported template is actually ready, and not sending the same email twice. Everything is additive — code written against 2.6.0 keeps compiling and sends byte-identical requests.

### Added

- **`preparation_status` on every template response** — `Template`, `TemplateDetail`, `CreatedTemplate` and `UpdatedTemplate`, typed as the new `Enums\TemplatePreparationStatus` (`Pending`, `Ready`, `Failed`).

  Creating or updating a template through the API defers image migration and HTML rendering to a background job. On a create with JSON there is no HTML at all until that finishes. On an **update** the previous render stays in place, so the template is still sendable — but it is serving the *old* content until this reads `Ready`. There is a `->isSettled()` helper for the "is what I sent what will go out" question.

  Read it as `Ready` when the API omits the key, not `Pending`: an older API deployment simply did not have the field, and every template with HTML was usable there. Defaulting to `Pending` would make an old API look like a stalled queue and hang anything that waits for readiness.

- **`ListTemplatesFilter::folderId()`** — narrows the list to one folder. This is what makes reconciling a bulk import cheap: one `perPage(100)` call for the whole folder instead of a detail call per template, each of which drags the full HTML payload against the same rate limit. A folder that is not in the resolved project answers **404**, not an empty list, so a typo cannot be misread as "nothing arrived yet".

- **`Idempotency-Key` on sends.** `EmailService::send()` takes an optional key, and `EmailBuilder::idempotencyKey()` sets one fluently:

  ```php
  $lettr->emails()->send(
      $lettr->emails()->create()->from(...)->to(...)->subject(...)->html(...),
      idempotencyKey: 'order-confirmation-12345',
  );
  ```

  Reuse the same key when you retry and the API returns the original result instead of sending a second email. **You supply the key; the SDK never generates one.** The SDK does not retry — one `send()` is one HTTP request — so the retry is yours, and only you know that two calls are the same logical send. A key minted inside `send()` would differ on every attempt and protect nothing.

  New `ValueObjects\IdempotencyKey` validates the format (1–255 characters of `[A-Za-z0-9._-]`) locally, so a malformed key throws `InvalidValueException` on your machine instead of costing a round trip and a 422. `IdempotencyKey::forPayload()` derives a deterministic key for callers with no natural id — opt into it knowingly, because two *deliberately* identical sends within the provider's 24 hour window then collapse into one.

- **`SendEmailResponse::$replayed`** — true when the response replayed an earlier send under the same key. No second email went out, and it is still a success.

- **Two distinguishable 409s**, because one is safe to retry and the other is not:
  - `IdempotencyInProgressException` — the original send is still processing. Retry with the **same** key, after `->retryAfter` seconds. A fresh key would send a second email.
  - `IdempotencyConflictException` — that key was already used with a different payload. A bug on your side; retrying fails forever.

  Both extend `ConflictException`, so existing `catch (ConflictException)` and `catch (ApiException)` handlers keep working unchanged.

- **`Contracts\SupportsRequestHeaders`** — a small second interface, implemented by `Client`, for sending per-request headers.

### Notes

- **`TransporterContract` is untouched**, so custom transporters keep compiling. Sending a header needed a way through the transporter, and adding an argument to `TransporterContract::post()` would have broken every class implementing it — a major release for a feature most callers will not use. The trade is that a transporter which does not implement `SupportsRequestHeaders` silently sends no idempotency key rather than failing; add the interface and one method to opt in.
- **Keys are scoped per team *and* API key.** The same string sent through a different API key is a different key and will not deduplicate. Worth knowing if you run several workers with separate keys.
- The provider retains a key for **24 hours**.
- `SendEmailData` carries `idempotencyKey`, but `toArray()` deliberately leaves it out — it travels as a header, not in the body.

## [2.6.0] - 2026-09-07

Covers the marketing side of templates: a template now says which module it belongs to, and the folders it can be filed into are listable. Everything here is additive — code written against 2.5.2 keeps compiling and sends byte-identical requests.

### Added
- **`Enums\TemplatePurpose`** (`Transactional`, `Campaign`) — which module a template belongs to. The two do not mix: only campaign templates can be picked by the campaign builder, and only transactional ones can be sent as single emails.
- **Create a marketing template.** `CreateTemplateData` takes an optional `purpose`:

  ```php
  $lettr->templates()->create(new CreateTemplateData(
      name: 'October Newsletter',
      json: $topolJson,
      purpose: TemplatePurpose::Campaign,
  ));
  ```

  It is emitted only when set, so omitting it sends no `purpose` key at all and the API applies its own default (transactional).
- **`purpose` on every template response** — `Dto\Template\Template`, `TemplateDetail`, `CreatedTemplate` and `UpdatedTemplate`. Always a `TemplatePurpose`, never null: a response without the key (an API that has not deployed the field yet) reads as `Transactional`, which is what such a template is.
- **Filter the list by module.** `ListTemplatesFilter` takes a `purpose`, fluently too — `ListTemplatesFilter::create()->purpose(TemplatePurpose::Campaign)`. Omitting it returns both modules, exactly as before.
- `TemplateCollection::filterByPurpose()`, alongside the existing `filterByProject()` and `filterByFolder()`.
- **Folders are listable — `$lettr->folders()`.** `FolderService::list(?ListFoldersFilter)` wraps `GET /folders` and returns a `ListFoldersResponse` (a `FolderCollection` plus pagination). Each `Dto\Folder\Folder` carries `id`, `name`, `projectId`, `purpose`, `templatesCount` and timestamps.

  This is what `CreateTemplateData::$folderId` was missing: nothing in the SDK ever returned a folder id, so a caller either omitted `folderId` and accepted whichever folder the API picked, or hardcoded an integer read out of an app URL by hand. Now:

  ```php
  $campaigns = $lettr->folders()
      ->list(ListFoldersFilter::create()->purpose(TemplatePurpose::Campaign))
      ->folders
      ->first();

  $lettr->templates()->create(new CreateTemplateData(
      name: 'October Newsletter',
      folderId: $campaigns?->id,
      json: $topolJson,
      purpose: TemplatePurpose::Campaign,
  ));
  ```

  `ListFoldersFilter` takes `projectId`, `purpose`, `perPage` and `page`; without a `projectId` the team's default project is used, the same way `templates()->list()` resolves it. `FolderCollection` adds `first()`, `findById()`, `findByName()` and `filterByPurpose()`.

  Read-only by design: creating, renaming and deleting folders stay in the app, because deleting one moves or deletes the templates inside it.

### Notes
- `UpdateTemplateData` deliberately has **no** `purpose`. `PUT /templates/{slug}` does not accept one, and moving a template across modules has to copy its versions and merge tags into the other module's folder — a separate endpoint that does not exist yet. Set the purpose at create time.
- The new constructor parameters were appended last on the existing DTOs, so positional construction keeps working. On `ListTemplatesFilter` that puts `purpose` after `page`; the fluent `->purpose()` reads better and is the documented way.

## [2.5.2] - 2026-09-01

### Changed
- Allow `guzzlehttp/guzzle` `^8.0` alongside `^7.5`. Fresh Laravel 13 installs now lock Guzzle 8, which made `composer require lettr/lettr-php` (and `lettr/lettr-laravel`) unresolvable without `-W`. No code changes were needed — the client only uses `Client::request()` with `headers`/`json`/`query` options, which are unchanged in Guzzle 8. The test suite and a live API smoke test pass on both Guzzle 7.15 and 8.1.

## [2.5.1] - 2026-08-15

### Fixed
- Corrected the segment condition documentation on `SegmentConditionGroup`: conditions **within a group** are joined by `OR`, and **groups** are joined by `AND` — i.e. `(A OR B) AND (C OR D)`. The previous docblocks stated the inverse. No behaviour change — the API has always evaluated segments this way, and no code paths were touched. Worth a read if you built a segment against the old description, since it may target a wider or narrower audience than you intended.

## [2.5.0] - 2026-08-13

Covers the reworked bulk contact import. Everything here is additive — code written against 2.4.0 keeps compiling and sending the exact same payloads.

### Added
- **Per-contact bulk create.** `BulkCreateAudienceContactsData` now supports a second request shape where each contact carries its own properties, lists and topic subscriptions, alongside the original flat `emails` list. Two named constructors make the choice explicit:
  - `BulkCreateAudienceContactsData::forEmails($emails, $listId, $properties, $listIds, $topics, $updateExisting)` — the original shape.
  - `BulkCreateAudienceContactsData::forContacts($contacts, $listIds, $topics, $properties, $updateExisting)` — one `BulkAudienceContactRow` per contact.

  The plain constructor still accepts `(emails, listId, properties)` positionally, so existing calls are untouched; the new fields were appended as optional parameters. Exactly one of `emails`/`contacts` must be non-empty — an empty payload now throws `InvalidValueException` instead of being sent to the API.
- New request DTOs: `BulkAudienceContactRow` (`email`, `properties`, `listIds`, `topics`) and `AudienceTopicSubscription` (`id` + state, with `AudienceTopicSubscription::optIn()` / `::optOut()` shortcuts).
- New enum `AudienceTopicSubscriptionState` (`opt_in`, `opt_out`) for what a request should *do* with a topic. Deliberately separate from the existing `AudienceTopicDefaultSubscription`, which describes how a topic behaves for new contacts. An `optOut()` on a topic whose default is opt-out suppresses the auto-subscription in the same request, instead of needing a second call.
- **Batch-wide `listIds` and `topics`,** plus `updateExisting`, on `BulkCreateAudienceContactsData`. Batch-wide lists and topics are unioned into every row; a row-level property key or `opt_out` wins over the batch-wide value. `updateExisting: true` merges properties (submitted keys overwrite, absent keys are preserved) and allows dropping a subscription; it is only emitted when `true`, so legacy payloads stay byte-identical.
- **Bulk create now reports what happened per row.** `BulkStoreAudienceContactsResult` gains `updated`, `errorCount`, `errors` (`BulkAudienceContactError[]` — `index`, `email`, `errorCode`, `error`) and `contacts` (`BulkAudienceContactRef[]` — `id`, `email`, `created`), plus the helpers `hasErrors()`, `contactIds()` and `idFor(string $email)`. `created` and `alreadyExisted` keep their exact meaning, and all new fields default when the API omits them, so the DTO also reads a pre-TPL-2105 response.

  A bulk create can **partially succeed**: rows that fail validation are skipped and returned in `errors` while the rest of the batch commits, and the call still returns HTTP 201. Check `hasErrors()` — do not read a successful return as "everything landed".

  Note that `alreadyExisted` and `updated` overlap by design. They answer different questions ("was the address already in the audience?" vs "did this request change the contact?"), so they do not sum to the row count: a contact that already existed and got attached to a list is counted in both.
- New enum `BulkAudienceContactErrorCode` (`missing_email`, `invalid_email`, `invalid_property_value`, `unknown_property_key`, `unknown_list`, `unknown_topic`, `invalid_topic_subscription`) with a `message()` helper. `BulkAudienceContactError->errorCode` is typed `BulkAudienceContactErrorCode|string`, so a code added server-side survives as a raw string instead of throwing `ValueError`.
- **Bulk topic subscribe/unsubscribe** — 2 new endpoints on `$lettr->audience->contacts()`, mirroring the existing `bulkAttachLists()`/`bulkDetachLists()` pair:
  - `bulkSubscribeTopics(BulkAudienceContactTopicsData)` — `POST /audience/contacts/topics/bulk`, returns `BulkSubscribeContactsToTopicsResult` (`subscribed`, `alreadySubscribed`, `totalPairs`).
  - `bulkUnsubscribeTopics(BulkAudienceContactTopicsData)` — `DELETE /audience/contacts/topics/bulk` with a request body, returns `BulkUnsubscribeContactsFromTopicsResult` (`unsubscribed`, `totalPairs`). Pairs that did not exist are ignored.

  Both process every `contactIds × topicIds` combination (up to 1000 × 50). A single `BulkAudienceContactTopicsData` serves both directions. Feed them `$result->contactIds()` from a bulk create — no id lookup needed.
- `ApiException::errorCode()` (and the readonly `->errorCode` property) exposes the machine-readable `error_code` from the response body on every API exception, or `null` when the API did not send one. The raw string is kept so a code added server-side is still readable.
- `ContactAlreadyExistsException` — thrown by `$lettr->audience->contacts()->create()` when the email is already in the team's audience. It carries the colliding `->email`. This is a client-correctable condition, **not** an outage: do not retry it; update the existing contact with `update()`, or use `bulkCreate()` with `updateExisting: true`.

### Changed
- `Exceptions\ConflictException` is no longer `final`, so `ContactAlreadyExistsException` can extend it. Existing `catch (ConflictException)` and `catch (ApiException)` handlers catch the new exception unchanged.
- Creating a contact whose email already exists now surfaces as `ContactAlreadyExistsException` (HTTP 409, `resource_already_exists`). The API previously let this escape as HTTP 500 with the misleading `send_error` code, which arrived as a plain `ApiException`. **If your retry policy retries 5xx, duplicate creates are no longer retried** — which was pointless anyway. Any error mapping or docs of yours that name `send_error` for this endpoint should be corrected. A 409 with any other error code stays a plain `ConflictException`.

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
