# Planning & Execution Rule

## Core Directive:
**Always plan before implementation before execution.**

## Standard Operating Procedure:
1. **Pre-Implementation Planning**:
   - Thoroughly research the problem, analyze existing components, layouts, state management, and user intent.
   - Create or update the `implementation_plan.md` artifact detailing the proposed changes, design decisions, and verification steps.
   - Request and await explicit user approval before executing any file modifications.

2. **Pre-Execution Bug & Regression Checking**:
   - Review affected files for edge cases, null safety, type mismatches, and responsive design compatibility.
   - Ensure all UI components adhere to professional design standards (clean enterprise aesthetics, no native browser dialogs, responsive layout).
   - Validate that backend API contracts and authentication/role guards remain consistent.

3. **Post-Execution Verification**:
   - Perform automated or manual verification of all modified views and workflows.
   - Document results in `walkthrough.md`.
