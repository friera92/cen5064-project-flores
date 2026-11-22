# NeighbourLend — Dispute Resolution Business Rules v1

**Version:** 1.0  
**Date:** 2026-11-21  
**Status:** Proposed — business rules for the next implementation phase  
**Scope:** Administrative resolution of disputes arising from equipment reservations  
**Baseline:** [Commit `099edea`](https://github.com/friera92/cen5064-project-flores/commit/099edea863348f2d12149b428e5d5e21c548aa7c)

> **Implementation note:** This document defines intended business behavior, not a claim that dispute resolution, compensation, infractions, or account suspension have already been implemented. Any policy that affects existing users or financial obligations requires appropriate user notice and acceptance before enforcement.

## 1. Purpose and principles

NeighbourLend needs a fair, auditable process for resolving disagreements about the condition of equipment following a reservation. An administrator acts as a **neutral arbitrator**, not as a lender or borrower.

The process follows six principles:

1. Both parties have an opportunity to provide evidence and a statement.
2. Decisions are based on documented evidence, not on which party filed the dispute.
3. **Financial responsibility and misconduct are separate findings.** Losing a dispute does not automatically generate an infraction.
4. The administrator may resolve a dispute but **may not create, modify, delete, or retire a user's tool**.
5. A reservation's resolution, a tool's availability status, and a user's account status are separate concerns.
6. Every material administrative action must be traceable and reviewable.

## 2. Existing system behavior and boundaries

As of the baseline commit:

- The reservation lifecycle supports `REQUESTED`, `APPROVED`, `ACTIVE`, `RETURNED`, `CLOSED`, `CANCELED`, and `DISPUTED`.
- After return, the lender records a `returned_condition` and optional `inspection_notes`.
- Inspecting a tool as `DAMAGED`, `BROKEN`, or `MISSING_PARTS` changes the reservation to `DISPUTED`.
- The tool becomes `MAINTENANCE` for `DAMAGED`, or `RETIRED` for `BROKEN` / `MISSING_PARTS`.
- The admin dashboard provides a **read-only dispute list** with search/filter capabilities; participants and the admin can see inspection notes.
- Administrative decision-making, evidence uploads, infractions, suspensions, and compensation settlement are **not yet implemented**.
- At present, `DISPUTED` is terminal in the reservation state machine. A future authorized transition to `CLOSED` will be required to finalize resolved disputes.

**Scope boundary:** v1 focuses on disputes caused by post-return inspection. Independent borrower-initiated disputes, payment processing, and automated damage valuation are outside the initial implementation scope.

## 3. Roles and authority

| Role | Allowed in a dispute | Not allowed |
| --- | --- | --- |
| **Administrator** | Review evidence, contact parties, document findings, issue decisions, assess compensation, confirm infractions, finalize disputes | Act as lender/borrower; create, edit, delete, or change availability of tools; directly charge users without a separate payment workflow |
| **Lender** | Report an issue during inspection, submit evidence, respond to questions, request a review, manage their own tool subject to existing restrictions | Decide the administrative outcome; impose infractions or suspend accounts |
| **Borrower** | Submit evidence and testimony, contest allegations, request a review, fulfill return/other existing obligations | Decide the administrative outcome; alter a lender's tool |

A regular user may act as lender in one reservation and borrower in another. An **administrator account cannot participate in either role**.

## 4. Investigation and evidence

### BR-01 — Case opening

A dispute case is opened when the lender's return inspection places a reservation in `DISPUTED`. Preserve the original returned condition, inspection notes, reservation details, parties, and relevant timestamps.

### BR-02 — Evidence collection

The administrator must seek relevant evidence from **both parties**, including when available:

- Photographs showing the tool **before** handoff and **after** return.
- Original listing description and declared tool condition.
- Lender inspection notes, borrower response, and return/handoff records.
- Repair estimates, receipts, replacement estimates, or other supporting documents.

Evidence records must identify the submitting party, submission time, type, and related case. Original submissions should be preserved; later additions or corrections should be recorded separately. Older reservations must **not** be penalized solely because pre-handoff photographs were never required by the application.

### BR-03 — Statements and contact attempts

The administrator must offer the lender and borrower a chance to give their account by **email or telephone**. Record the contact method, timestamp, concise summary, and whether the party responded. Telephone summaries should distinguish the administrator's notes from the participant's exact words. Do not record calls by default; recording requires a separate compliant consent process.

A party's failure to respond is documented, but **silence is not an admission of responsibility**. Cases may be decided on available evidence after reasonable notice and the response deadline.

### BR-04 — Suggested service deadlines

| Event | Proposed v1 target |
| --- | --- |
| Lender reports post-return issue | Within **48 hours** after return, where feasible; documented exceptions permitted |
| Parties submit statements/evidence | Within **5 calendar days** of notification |
| Administrator issues an initial decision | Target **7 calendar days** after the evidence window closes |
| Either party requests review | Within **7 calendar days** of decision notice |

These are **proposed policy deadlines**, not existing application validations. Enforcement, notification delivery, exceptions, and time-zone rules must be finalized before launch. The administrator can extend the evidence period for documented reasons and must notify both parties.

## 5. Findings and resolution outcomes

### BR-05 — Outcome categories

Each decided case must have exactly one primary outcome:

| Outcome | Meaning | Normal consequence |
| --- | --- | --- |
| `BORROWER_RESPONSIBLE` | Evidence supports that the borrower is responsible for qualifying damage/loss | Compensation assessment may apply; infraction **only if misconduct is separately confirmed** |
| `LENDER_RESPONSIBLE` | Evidence supports that the issue is attributable to the lender or predates the reservation | No borrower compensation; infraction only for independently confirmed misconduct |
| `INSUFFICIENT_EVIDENCE` | Evidence does not sufficiently establish responsibility | No imposed compensation or infraction from the unresolved allegation |
| `SHARED_RESPONSIBILITY` | Evidence supports contributions from both parties | A reasoned partial allocation may be considered |

### BR-06 — Separate misconduct finding

The administrator may additionally record `FALSE_CLAIM_CONFIRMED` or another documented misconduct finding when there is affirmative evidence of intentional deception or a serious rule violation. **A claim being rejected is not proof that it was knowingly false.** Findings must cite relevant evidence and written reasoning.

### BR-07 — Decision record

Every decision must contain the primary outcome, explanation, evidence references, parties notified, decision timestamp, responsible administrator, and—where relevant—separate compensation and infraction determinations. Neither party may edit the administrative decision.

## 6. Compensation assessment

### BR-08 — Eligibility and basis

A lender may receive a **compensation assessment** only when qualifying damage or loss is supported by evidence and responsibility is attributed to the borrower, wholly or partly. Exclude normal wear and tear, pre-existing damage, and unsupported costs.

Compensation should be based on reasonable, documented loss:

- **Repairable tool:** justified repair cost, normally not exceeding the tool's documented pre-damage fair value.
- **Irreparable or missing tool:** reasonable current value of an equivalent used item, considering age, condition, depreciation, and supporting quotes.
- **Shared responsibility:** an explicitly justified share of eligible loss, never an automatic 50/50 allocation.

**Illustrative example:** If a drill has a supported current value of $70 and a justified repair quote of $35, the proposed assessment is $35, assuming the borrower is fully responsible and the repair restores its prior condition.

### BR-09 — Assessment is not payment

In v1, the administrator **records a proposed or agreed amount**; the platform does **not** automatically charge the borrower, guarantee reimbursement, transfer money, or mark funds as paid. A future payment feature requires separate rules for authorization, collection, refunds, fees, and disputes about charges. Do not label an assessment as a completed payment.

## 7. Infractions and suspensions

### BR-10 — Grounds for an infraction

Infractions require **affirmatively established misconduct**, not merely an unfavorable outcome. Examples include negligent or prohibited use established by evidence, deliberate concealment of damage or missing parts, and knowingly false evidence or claims by **either** lender or borrower.

Normal wear, an accidental incident without established misconduct, a good-faith but unsuccessful claim, and insufficient evidence do **not** independently justify an infraction. A single incident produces at most **one** infraction per responsible user, regardless of how many related acts are described.

### BR-11 — Threshold and duration

A user who accumulates **three confirmed infractions within a rolling 12-month period** becomes eligible for an automatic **30-calendar-day suspension**. Only final infractions count; an infraction under timely review does not count until the review is completed. A reversed infraction is removed from the active count.

A suspension is issued **once per qualifying threshold event**. Reaching the end of a 30-day suspension must not trigger a new suspension from the same unchanged set of infractions; a subsequent confirmed infraction may trigger a new evaluation.

### BR-12 — Suspension effects

During suspension:

- The user **cannot create new reservations** or publish new listings.
- The user's existing tools are **hidden from the public catalog**, without changing their underlying condition/status or deleting them.
- The user retains **limited account access** to view the sanction, supply dispute evidence, request a review, and fulfill obligations for existing rentals.
- Already-active loans and required returns remain manageable. The treatment of future confirmed bookings must be separately specified before enforcement; suspensions must not silently abandon active obligations.

After 30 days, restrictions expire automatically unless another independently authorized restriction applies. Previously listed tools become visible again **only if otherwise eligible**; `MAINTENANCE` or `RETIRED` tools do not automatically become `AVAILABLE`.

### BR-13 — Equal application

The same infraction and suspension policy applies to lenders and borrowers. A lender found to have deliberately fabricated evidence may receive an infraction even when their original claim was for damage to their own tool.

## 8. Finalization, appeals, and state separation

### BR-14 — Dispute case workflow

Dispute administration has a **separate case status** from the reservation state:

```text
EVIDENCE_COLLECTION
        |
        v
   UNDER_REVIEW
        |
        v
      DECIDED
      /     \
     v       v
  APPEALED  CLOSED
     |
     v
   CLOSED
```

- `DECIDED` is an initial decision communicated to both parties.
- `APPEALED` means a timely review request is pending.
- `CLOSED` means the case has a final decision and no pending review.

### BR-15 — Review of a decision

Either party may request a review within **7 calendar days** of notice, explaining the alleged factual or procedural error and supplying relevant new evidence when available. A timely review pauses final enforcement of contested compensation or sanctions. Prefer a different reviewer when possible; if only one administrator is available, record a separate, reasoned reconsideration.

### BR-16 — Reservation closure

After the decision becomes final (review completed or review window expired), the **administrator** may finalize the case and transition its reservation from `DISPUTED` to `CLOSED`. The lender and borrower must not be able to bypass this process with a normal close action.

### BR-17 — Tool condition and owner control

Administrative closure **does not modify** `Tool.availability_status`. A tool that entered the dispute as `MAINTENANCE` or `RETIRED` remains in that status until its **owner**, through permitted application workflows, decides what to do. The administrator may **recommend** inspection, repair, retirement, or removal, but **cannot perform those actions for the owner**. Existing restrictions on unsafe or unavailable listings still apply.

## 9. Transparency, privacy, and auditability

### BR-18 — Notifications

Notify both parties when evidence is requested, a deadline is extended, a decision is issued, a review is requested, or a case is closed. Notify affected users separately about an infraction or suspension, including reason, effective dates, and review instructions. Email/phone contact is acceptable for investigation; the final notification mechanism is an implementation decision.

### BR-19 — Data visibility and confidentiality

Each participant may see the case outcome and relevant evidence needed to respond to allegations, subject to redaction of unrelated personal data. **Private administrator working notes** and unrelated contact information must not be shown automatically to both parties. Access to uploaded evidence must be authorized by case participation or administrative role. Evidence retention and deletion periods require a separate policy.

### BR-20 — Immutable audit trail

Record key events: case creation; evidence submissions; contact attempts; deadline changes; decisions; misconduct findings; compensation assessments; review requests; infraction changes; suspensions; and final closure. Preserve who performed each action, when, and why. Corrections are recorded as new events rather than silently overwriting history.

### BR-21 — Policy notice and non-retroactivity

The rules governing economic liability, strikes, and suspension must be disclosed and appropriately accepted **before** they can be enforced against users. Do not retroactively impose new charges, infractions, or deadlines on reservations created under earlier policies. Legal and payment-service requirements must be reviewed before launching enforceable monetary collection.

## 10. Illustrative scenarios and acceptance criteria

| Scenario | Expected behavior |
| --- | --- |
| Borrower is responsible for accidental damage without proven misconduct | Compensation may be assessed; **no automatic infraction** |
| Borrower deliberately misuses a tool and causes damage | Compensation may be assessed; misconduct finding may generate **one** infraction |
| Lender claims damage but available evidence does not establish who caused it | `INSUFFICIENT_EVIDENCE`; **no imposed compensation or infraction** |
| Lender knowingly submits fabricated before/after photos | Confirmed misconduct may generate **one lender infraction** |
| User reaches three final infractions in rolling 12 months | One **30-day suspension**; listings hidden; essential existing-rental actions remain accessible |
| User's third infraction is overturned on review | Recalculate total and remove any suspension based solely on that overturned infraction |
| Disputed tool is `RETIRED` when admin finalizes the case | Reservation may close; tool **remains `RETIRED`** |
| Admin attempts to delete or modify a tool | Operation is **denied**, regardless of dispute outcome |
| Admin records a $35 compensation assessment | Case shows **assessment**, not an automatically processed payment |
| User requests a timely review | Case enters `APPEALED`; contested final sanctions or compensation are held until review concludes |

## 11. Suggested implementation order

1. **Evidence management:** uploads, testimony/contact logs, deadlines, permissions.
2. **Case resolution:** admin decision workflow, outcome, reason, notifications, authorized reservation closure.
3. **Misconduct and sanctions:** infractions, rolling window, limited-access suspension, listing visibility, reactivation.
4. **Compensation assessment:** evidence-backed amounts and transparent records, without automatic payment.
5. **Expanded review and payments:** richer appeal workflow, payment settlement, refunds, and reconciliation.

A **minimal review request and enforcement hold** are necessary even if a dedicated appeals interface is deferred.

## 12. Open decisions before implementation

- What level of photographic evidence is mandatory for **new** reservations, and how is the handoff condition acknowledged?
- Which party has responsibility for producing repair estimates, and how are disputed valuations reviewed?
- How should already-approved **future** bookings be handled when an account becomes suspended?
- What exact notification channel and time zone define deadlines and decision receipt?
- What are the evidence retention/deletion periods and secure storage requirements?
- Who conducts a review when the platform has only one administrator?
- Will compensation remain a documented recommendation, or will a separate payment feature be approved later?

**Change control:** Proposed adjustments to this document should be reviewed as business-rule changes and versioned. Architectural decisions about storage, authorization enforcement, or payment infrastructure can be captured in separate ADRs under `docs/adr/`.
