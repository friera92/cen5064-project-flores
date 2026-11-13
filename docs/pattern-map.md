# Creational Design Patterns — NeighbourLend Pattern Map

**Project:** NeighbourLend — Community Equipment & Tool Sharing Hub  
**Scope:** Laravel backend (`neighbourlend/`)  
**Working branch:** `feature/creational-pattern`  
**Status:** Design review; proposed pattern has **not** yet been implemented  
**Code reviewed:** Backend snapshot provided in `Archive.zip`

## Purpose

This document evaluates the five Gang of Four (GoF) creational design patterns against NeighbourLend's existing architecture. The goal is to identify a pattern that addresses a genuine design need rather than introduce unnecessary abstraction simply to demonstrate a pattern.

NeighbourLend uses a layered Laravel backend:

`HTTP Controllers → Application Services → Repository Interfaces/Repositories → Eloquent Models → PostgreSQL`

The evaluation focuses on the actual code in the supplied backend snapshot. Usage of a Laravel framework feature is distinguished from a creational pattern explicitly designed and implemented by the NeighbourLend team.

## Pattern comparison

| Pattern | Already present? | Potential use in NeighbourLend | Decision |
|---|---|---|---|
| **Factory Method** | Laravel's `UserFactory` provides framework-managed model generation; no dedicated application-level GoF Factory Method identified | Select and create different implementations of a common service or object type when distinct variants are required | **Not selected now** |
| **Abstract Factory** | No | Create related families of services, such as multiple notification-channel implementations, if the product gains those variants | **Not justified currently** |
| **Builder** | Eloquent Query Builder is used in `ToolRepository::search()`; a NeighbourLend-specific Builder is not yet implemented | Construct a tool search query incrementally from optional catalog filters | **Recommended for implementation** |
| **Prototype** | No project-level prototype or cloning workflow identified | Duplicate a tool listing as a new draft, with identity and history reset | **Not justified currently** |
| **Singleton** | No project-level Singleton identified; providers use Laravel `bind()` rather than `singleton()` | Share a genuinely stateful, process-wide coordinator if a future requirement establishes one | **Not recommended for current services** |

## 1. Factory Method

**Intent:** Define an interface for creating objects while allowing a subtype or specialized creator to determine which concrete object to instantiate.

**Existing evidence:** `database/factories/UserFactory.php` extends Laravel's `Factory` class and provides `definition()` and `unverified()` for generating test users. This is an example of framework-supported object creation; it should **not** be presented as an independently designed GoF Factory Method in NeighbourLend's business layer. `ToolService::createTool()` delegates persistence to `ToolRepository::create()`, but this operation creates one Eloquent `Tool` type and does not select among polymorphic products.

**Possible use:** If NeighbourLend later supports different notification types, a common notification contract and specialized creators could construct the correct notification object. No such set of interchangeable notification products appears in the reviewed implementation.

**Decision:** **Do not introduce Factory Method now.** Current object creation is straightforward and already supported by Eloquent and Laravel factories. Adding a custom creator without distinct product variants would complicate the design without a clear benefit.

## 2. Abstract Factory

**Intent:** Provide an interface for creating *families of related objects* without coupling clients to their concrete implementations.

**Existing evidence:** There are repository interfaces and service-provider bindings, including `ToolRepositoryInterface` and `ReservationRepositoryInterface`. These apply **Dependency Inversion and dependency injection**; they are not an Abstract Factory. The reviewed code does not create multiple related product families selected by a shared configuration.

**Possible use:** A future communication subsystem might provide related services for email and SMS (for example, a sender, formatter, and delivery-status handler for each channel). An Abstract Factory could keep each family consistent.

**Decision:** **Not appropriate at present.** NeighbourLend does not currently need to switch between families of related implementations. A factory hierarchy would increase maintenance without solving an existing problem.

## 3. Builder

**Intent:** Construct a complex product step by step, separating construction from use and allowing different combinations of options.

**Existing evidence:** `app/Repositories/ToolRepository.php` contains `search(array $filters)`. It begins with `Tool::query()->with('category:id,name')` and conditionally adds:

- owner (`owner_id`);
- case-insensitive title search (`q`);
- category (`category_id`);
- tool condition (`condition`);
- availability (`availability_status`);
- minimum and maximum daily rate (`min_rate`, `max_rate`);
- ordering and pagination (`per_page`).

This already uses **Laravel's Eloquent Query Builder**. However, the conditional construction logic remains inside the repository; NeighbourLend has **not yet implemented its own named Builder abstraction**.

**Proposed application:** Introduce `app/Builders/ToolSearchBuilder.php` to encapsulate the incremental construction of a filtered Eloquent query. A proposed API could expose `forOwner()`, `matchingText()`, `inCategory()`, `withCondition()`, `withAvailability()`, `minRate()`, `maxRate()`, and `build()`.

**Proposed flow:**

`ToolController → ToolService → ToolRepository → ToolSearchBuilder → Eloquent Builder → PostgreSQL`

The `build()` method would return a configured Eloquent query; the repository would remain responsible for ordering/pagination and returning `LengthAwarePaginator`. This preserves the existing repository interface and public API contract.

**Benefits:**
- separates *how filters are assembled* from *how the query is executed*;
- makes optional filters easier to combine and test;
- creates a focused extension point as catalog search grows;
- retains Eloquent's parameter binding and Soft Deletes behavior.

**Trade-off:** The framework already supplies a capable builder. A custom wrapper is worthwhile only if it meaningfully simplifies or reuses the search logic; a thin class that merely renames each `where()` call would add little value. No separate Director class is required for this use case.

**Decision:** **Recommended**, subject to implementation and tests in `feature/creational-pattern`.

## 4. Prototype

**Intent:** Create a new object by copying an existing prototype instead of constructing all its properties from scratch.

**Existing evidence:** Tools are created from validated attributes through `ToolService` and `ToolRepository`. No tool-duplication feature or explicit application-level `clone`/`replicate()` workflow is present in the reviewed code.

**Possible use:** A lender might duplicate an existing listing to create a new draft for similar equipment.

**Risks:** A copied tool must get a **new ID**, appropriate owner permissions, a safe initial availability state, and no inherited reservations, reviews, or deletion timestamp. Copying the whole Eloquent model indiscriminately could produce incorrect domain state.

**Decision:** **Not needed currently.** Consider it only when a user-facing *Duplicate listing* feature is requested. A plain data-copy operation should not automatically be described as a full Prototype implementation without an explicit cloning contract.

## 5. Singleton

**Intent:** Restrict a class to one instance and expose a global access point to it.

**Existing evidence:** The reviewed Laravel providers register repositories using `$this->app->bind(...)`, including `ToolRepositoryInterface` and `ReservationRepositoryInterface`. This instructs the container how to resolve dependencies; **`bind()` does not guarantee a single shared instance**. No application-level Singleton implementation was identified in the supplied code.

**Possible use:** A future application-wide, stateful coordinator could warrant a shared instance if its lifecycle and consistency requirements justify one.

**Why it is not suitable now:** NeighbourLend's services and repositories primarily coordinate requests and database operations. Their data already lives in PostgreSQL, and Eloquent/transaction boundaries handle persistence. Making these classes global Singletons would add shared-state and testing concerns without guaranteeing cross-process consistency: a Laravel container singleton is scoped to an application/container lifecycle, not to every server or worker.

**Decision:** **Do not implement Singleton merely to share service instances.** Continue using constructor injection and repository interfaces.

## Selected pattern and implementation boundary

**Selected pattern:** **Builder**, applied specifically to tool catalog search.

**Planned changes:**
1. Add `neighbourlend/app/Builders/ToolSearchBuilder.php`.
2. Refactor `ToolRepository::search()` to delegate filter construction to the new builder.
3. Keep `ToolRepositoryInterface::search(array $filters): LengthAwarePaginator` unchanged.
4. Preserve the API behavior, default ordering, pagination, and automatic Soft Deletes filtering.
5. Add automated tests for unfiltered queries, combined filters, owner scoping, price bounds, and exclusion of soft-deleted tools.

**Out of scope:** Nuxt frontend changes, reservation state transitions, database schema changes, and changing the business rules of Tools CRUD.

**Validation:** Run PHP syntax checks and Laravel tests against an isolated test database. At the time of this pattern map, the provided `tests/` directory contains only Laravel ExampleTests; Builder-specific test coverage is **planned, not completed**.

## Conclusion

**Builder is the strongest fit among the five creational patterns for the current backend.** Catalog queries have multiple independent, optional parameters and are already assembled step by step. Extracting that construction into a focused application-level Builder can improve readability, reuse, and testability while preserving the repository and Eloquent layers.

Factory Method, Abstract Factory, Prototype, and Singleton are valid patterns, but their typical motivations are not sufficiently present in NeighbourLend's current requirements. They should be introduced only if future features create a concrete need.

---

*Documentation note: This map describes the backend as supplied for review, not a verified runtime build. Reevaluate the status of each pattern after future implementations or refactors.*
