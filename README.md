# Drupal Principal Developer - Technical Assessment

A curated collection of Drupal modules designed for evaluating senior-level PHP and Drupal expertise through code review and architectural discussions.

---

## Overview

This repository contains two custom Drupal 10 modules that serve as practical evaluation tools for Principal Developer candidates. The exercises focus on real-world scenarios involving performance optimization, architectural design, and adherence to Drupal best practices.

### Technical Specifications

| Specification | Details |
|--------------|---------|
| **Drupal Version** | 10.x |
| **PHP Version** | 8.1+ |
| **Assessment Duration** | 30-45 minutes |
| **Format** | Code review + verbal discussion |

> **Note**: This repository contains sample code created solely for interview purposes. No proprietary information, production code, or organizational data is included.

---

## Repository Structure

```
bca-drupal-interview/
├── README.md
└── web/
    └── modules/
        └── custom/
            ├── bca_reports/          # Module 1: Code Review
            │   ├── bca_reports.info.yml
            │   ├── bca_reports.routing.yml
            │   └── src/
            │       ├── Controller/
            │       │   └── ReportController.php
            │       └── Plugin/
            │           └── Block/
            │               └── RecentReportsBlock.php
            │
            └── bca_task/             # Module 2: Architecture Design
                ├── bca_task.info.yml
                ├── bca_task.services.yml
                └── src/
                    └── Service/
                        └── ReportEntitlementService.php
```

---

## Assessment Modules

### Module 1: `bca_reports` — Code Review Exercise

**Objective**: Evaluate the candidate's ability to identify issues in existing code and propose improvements.

**Description**: A report listing module that provides both a page controller and a block plugin for displaying published reports. The module contains various implementation patterns that require critical analysis.

**Key Files**:
- `ReportController.php` — Controller handling report list display
- `RecentReportsBlock.php` — Block plugin for sidebar display

**Evaluation Criteria**:
- ✓ Performance optimization and query efficiency
- ✓ Proper use of Drupal's dependency injection system
- ✓ Cache API implementation and invalidation strategies
- ✓ Code architecture and separation of concerns
- ✓ Error handling and logging practices
- ✓ Adherence to Drupal coding standards

---

### Module 2: `bca_task` — Architectural Design Exercise

**Objective**: Assess the candidate's approach to designing robust, scalable service architectures.

**Description**: A service skeleton for managing user entitlements through external API integration. The service includes method signatures with pseudo-code comments, designed to facilitate discussion about implementation strategies.

**Key File**:
- `ReportEntitlementService.php` — Service interface with documented method expectations

**Discussion Topics**:
- ✓ HTTP client integration patterns with Guzzle
- ✓ Cache strategy design (keys, TTL, invalidation)
- ✓ Exception handling for network failures and API errors
- ✓ Data validation and normalization approaches
- ✓ Unit testing strategies for external dependencies

---

## Getting Started

### Clone the Repository

```bash
git clone https://github.com/iamarsh/bca-drupal-interview.git
cd bca-drupal-interview
```

### Review the Code

Navigate to the module directories and examine the implementation:

```bash
# Module 1: Code Review
web/modules/custom/bca_reports/

# Module 2: Architecture Design
web/modules/custom/bca_task/
```

> **Important**: This repository is designed for code reading and discussion only. A working Drupal installation is not required.

---

## Assessment Guidelines

### For Candidates

1. **Allocate Time Appropriately**
   - Code Review: 15-20 minutes
   - Architecture Design: 15-20 minutes
   - Discussion: Remaining time

2. **Focus Areas**
   - Identify potential issues and explain their impact
   - Propose concrete solutions with justification
   - Consider scalability and maintainability
   - Reference Drupal best practices where applicable

3. **Discussion Format**
   - Be prepared to explain your reasoning
   - Discuss trade-offs between different approaches
   - Share relevant experience from past projects

---

## Additional Information

### Technologies & Frameworks

- **Drupal Core**: Entity API, Routing, Plugin System
- **PHP**: OOP, Dependency Injection, Exception Handling
- **Database**: Query API, Performance Optimization
- **Caching**: Cache API, Cache Tags, Cache Contexts

### Code Standards

All code follows Drupal coding standards and leverages standard Drupal APIs including:
- Entity Type Manager
- Cache Backend Interface
- Logger Channel Factory
- HTTP Client (Guzzle)
- Configuration Management

---

## License

This repository is provided as-is for technical assessment purposes. Sample code is not licensed for production use.

---

**Repository Purpose**: Technical interview and candidate evaluation
**Maintained for**: Principal Developer position assessments
**Last Updated**: December 2025
