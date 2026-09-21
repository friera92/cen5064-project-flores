# NeighbourLend: Community Equipment & Tool Sharing Hub

<!-- CI badge: after Session 4, replace ORG/REPO and the workflow filename, then uncomment:
![CI](https://github.com/ORG/REPO/actions/workflows/ci.yml/badge.svg)
-->

**Student:** Leduan Flores · **Course:** CEN 5064 Software Design, Fall 2026 · **Partner:** Dioni Dinza

## Project

NeighbourLend is a monolithic, three-tier web application designed to facilitate peer-to-peer lending of tools, outdoor equipment, and household appliances within a local community or campus. The platform serves community members and students who need temporary access to specialized equipment without purchasing it. Built over a single relational database with strict domain-layer business logic, its four core features include:

Catalog & Availability Filtering: Item discovery with dynamic availability verification based on reserved date ranges.

Reservation State Engine: An end-to-end booking workflow enforcing valid state transitions (Requested → Approved → Active → Returned → Inspected/Completed).

Double-Booking & Conflict Validation: Strict domain logic preventing overlapping active reservations for the same physical asset.

Dual-Sided Trust & Return Review System: Post-loan condition confirmation, dispute flagging, and peer reputation scoring.

## How to run

```
[Exact commands to build and run your system from a clean clone.
Update this every time the steps change — your partner and your
instructor will follow it literally on conference days.]
```

## Architecture

### Tier breakdown (Session 2 studio)

| Tier | Responsibilities in THIS system | Example Classes/Modules |
|------|--------------------------------|--------------------------|
| Presentation | Captures user input, handles session state, triggers form validation, and renders views or serializes JSON. | Authentication; UserDashboard**; ToolManagement; ToolCatalog; ToolRequest; ToolReturn; UserProfile**; UserReview**; |
| Service | Coordinates application workflows, authorization checks, and transaction boundaries. | ToolService; UserService; AuthService; ReservationService; ReviewService |
| Domain | Contains core business logic, invariant enforcement, and state transitions independent of the database or UI. | Tool; User; Reservation; Review |
| Data | Manages all direct queries, database migrations, model relationships, and transactional queries. | ToolStore; UserStore; ReservationStore; ReviewStore |

 **(Lender && Borrower)

### Tech Stack
- Frontend: Vue.js 3 (Composition API) for a lightweight, responsive, and component-driven user interface.
- Backend: PHP (Laravel) structured around a strict N-tier architecture (Presentation, Service, Domain, Data) to isolate business logic and routing.
- Database & ORM: A single relational database (MySQL/PostgreSQL) managed via Eloquent ORM to handle transactional safety, data constraints, and optimistic/pessimistic locking.

### C4 — Context & Container (Session 3 studio)

```mermaid
%% Replace this placeholder with YOUR system's context diagram.
flowchart TB
    subgraph Users ["Users"]
        borrower["Borrower<br/>[Person]<br/><br/>Rents tools and submits equipment reviews"]:::person
        lender["Lender<br/>[Person]<br/><br/>Lists owned tools and approves rentals"]:::person
        admin["Platform Admin<br/>[Person]<br/><br/>Manages categories, disputes, and user status"]:::person
    end

    neighbourlend["NeighbourLend Platform<br/>[Software System]<br/><br/>Provides peer-to-peer equipment sharing, lifecycle reservation tracking, and trust management"]:::system

    borrower -->|"Searches equipment & reserves tools<br/>[HTTPS/JSON]"| neighbourlend
    lender -->|"Publishes tools & manages handoffs<br/>[HTTPS/JSON]"| neighbourlend
    admin -->|"Moderates accounts & arbitrates disputes<br/>[HTTPS/JSON]"| neighbourlend
```

```mermaid
flowchart TB
    subgraph Users
        U1["Lender"]
        U2["Borrower"]
        U3["Admin"]
    end

    subgraph Frontend ["Client Tier"]
        UI["Neighbour Lend Web UI\n(SPA / Web Application)"]
    end

    subgraph Backend ["Backend Tier (Server-Side)"]
        API["Laravel REST API\n(Controllers, Services, Policies)"]
    end

    subgraph Storage ["Persistence Tier"]
        DB[("PostgreSQL Database\n(Users, Tools, Reservations, Reviews)")]
    end

    U1 -->|"Manages tools, approves handoff"| UI
    U2 -->|"Browses items, books reservations"| UI
    U3 -->|"Moderates disputes, manages categories"| UI

    UI -->|"JSON / HTTPS\n(Sanctum Bearer Token)"| API
    API -->|"SQL / PDO\n(Transactions & Row Locks)"| DB
```

```mermaid
classDiagram
    class User {
        -id: Long
        -name: String
        -email: String
        -password: String
        -phone: String
        -address: String
        -picture: String
        -is_admin: Boolean       
    }

    class Category {
        -id: Long
        -name: String
        -description: String
    }

    class Tool {
        -id: Long
        -title: String
        -description: String
        -daily_rate: BigDecimal
        -availability_status: String
        -condition: String
        -picture: String
    }

    class Reservation {
        -id: Long
        -start_date: Date
        -end_date: Date
        -total_cost: BigDecimal
        -status: String
        -returned_condition: String
    }

    class Review {
        -id: Long
        -rating: Integer
        -comment: String
        -visible: Boolean
        -end_date: Date
    }

    User "1" --> "0..*" Tool : owns
    User "1" --> "0..*" Reservation : borrows
    User "1" --> "0..*" Review : writes

    Category "1" <-- "0..*" Tool : categorized under

    Tool "1" --> "0..*" Reservation : booked in
    Reservation "1" --> "0..2" Review : produces
```

```mermaid
sequenceDiagram
    actor U as User (Borrower)
    participant UI as Web UI
    participant S as ReservationService
    participant D as Database

    U->>UI: Request tool booking (dates)
    UI->>S: POST /api/reservations

    Note over S: Check Authorization Policy<br/>(User is not tool owner)

    S->>D: Check availability (lockForUpdate)
    D-->>S: Tool available

    S->>D: Save reservation (Status: PENDING)
    D-->>S: Reservation confirmed

    S-->>UI: 201 Created (Reservation details)
    UI-->>U: Display booking confirmation
```

## Architecture Decision Records

Decisions live in [`docs/adr/`](docs/adr/). Start with ADR-001 in Session 4.

| # | Decision | Status |
|---|----------|--------|
| [001](docs/adr/adr-001.md) | [What I am building and why] | [proposed] |

## Weekly log (optional but recommended)

A one-line note per week keeps your commit story readable:

- Week 3 (Sep 21): creating new issue, setting up a branch
