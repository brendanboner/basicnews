There’s a quiet shift happening, and it’s no longer confined to software development. We tend to notice it first in code because that’s where AI tools arrived earliest. However, now the same pattern is appearing in network automation, product development, security operations, and even at the executive level. At every stage of decision, design, and implementation, we are introducing systems that can act with increasing independence, often justified by efficiency, speed, and competitive pressure.

As organizations built trust in these autonomous systems, the idea of keeping a “human in the loop” arose, assuming that the human remained the primary decision maker, reviewing outputs, validating intent, and applying context. That assumption is no longer holding in practice.

## The expanding surface of autonomy

What’s changed is not just capability, but scope. AI is no longer a tool used at the edge of workflows. It’s increasingly embedded in the middle of them: The network engineer may begin by asking for configuration suggestions and end up allowing automated remediation; the product manager may start with summarizing feedback and move toward letting the system prioritize features; the CEO may begin with briefings and gradually rely on AI to shape how decisions are framed.

None of these steps feel significant in isolation, but together they create systems where autonomy is distributed and only loosely supervised.

|Domain|Typical Use of AI Today|Where Autonomy Expands Quietly|
|---|---|---|
|Software Development|Code generation and refactoring|Direct commits and deployment actions|
|Network Operations|Configuration suggestions and anomaly detection|Auto remediation and policy changes|
|Product Development|Feature ideation and user insight synthesis|Roadmap prioritisation and trade-off decisions|
|Security Operations|Alert triage and threat correlation|Automated response and rule creation|
|Executive Decision|Briefings and scenario summaries|Strategic framing and investment direction|

The pattern is consistent. The system begins as an assistant and becomes an actor. The transition is gradual and often invisible.

## The illusion of “I approve”

In many of these systems, the safeguard is still framed as approval. A prompt is shown, a recommendation is made, and a human is asked to confirm. This is meant to preserve accountability, but in practice it compresses several responsibilities into a single action.

In software development, we can follow up on approval dwell time as a metric, but it doesn’t offer much in terms of the context. The most common approval is to access the files being analyzed, and this is usually an instantaneous approval. Rather than the approval being a proxy for understanding, intent, and validation, it’s instead used as a measure for how much we were paying attention to the process, and then how much friction we want to ignore.

Over time, as systems prove useful and friction builds, that approval becomes routine. It’s clicked through rather than reasoned through. I have done this regularly myself, knowing that mundane approval is treated the same as critical aspects. As an experienced engineer, I know that eventually the system will throw up a random rm approval, or a git clean to “fix up” the workplace. The system learns that it can proceed, and the human learns that intervention is rarely required. This isn’t a failure of the individual, it’s a property of the system design.

## Attribution is not provenance

We’re starting to see attempts to compensate for this through attribution. Code commits that include both human and AI authorship (even if the human was responsible for the entire codebase); logs that indicate which system generated a configuration; reports that note the involvement of an assistant. This is useful, but it’s not enough. Attribution tells you who was involved, provenance tells you what actually happened. Provenance would include the constraints under which the system operated, the data it had access to, the alternatives it considered, the safeguards it encountered, and the decisions it made along the way. Without that, we’re left with a record that is technically complete but insufficient—but that’s not what we are recording.

## Bounded autonomy and earned trust

What’s needed is a shift from binary control to bounded autonomy. Systems should be allowed to operate independently, but only within clearly defined and enforceable limits. Those limits should not be static. They should evolve based on demonstrated behavior. This isn’t a new concept. We already apply it in CI/CD pipelines, identity systems, and access control frameworks. Permissions are granted gradually and expanded based on trust. The difference is that we’ve not yet applied this thinking consistently to agentic systems.

|Capability Level|Description|Required Evidence Before Expansion|
|---|---|---|
|Suggestion Only|System proposes actions but cannot execute|Accuracy and relevance over time|
|Assisted Execution|System executes with explicit approval|No repeated unsafe or blocked actions|
|Conditional Autonomy|System acts within predefined policies|Consistent adherence to policy boundaries|
|Expanded Autonomy|System handles broader workflows independently|Auditable history and predictable behaviour|

The idea is simple: Autonomy isn’t granted, it’s earned, and it’s always bounded. In most of the cases discussed here, that trust measure is simply ignored, and we hope that it’s just losing money, not costing lives.

## Where approval actually matters

The challenge is that approval exists across many layers, and in each layer the path around it is often shorter than the path through it. The best answer is a responsibility proxy—including a provenance layer that separates the actor from the actions. Companies like Jentic have abstracted this to a certain degree, and protected “LLM in the wild” tools like Open-Claw from accessing your data directly.

|System or Domain|What Requires Approval|How It Is Commonly Bypassed|
|---|---|---|
|Source Code and Git|Commits, merges, dependency updates|Superficial reviews and bulk AI changes|
|CI/CD Pipelines|Build, test and deployment stages|Auto approvals and policy overrides|
|Network Infrastructure|Configuration changes and segmentation|Reused templates and inherited policies|
|Identity and Access|Role assignments and privilege escalation|Over broad roles and temporary exceptions|
|Data Access|Secrets, environment variables, databases|Debug access and misconfigured scopes|
|Security Tooling|Alerts, scans and remediation actions|Alert fatigue and auto dismissal|
|Product Planning|Feature prioritisation and roadmap decisions|Over reliance on AI generated insights|
|Executive Decisions|Strategy documents and investment direction|Delegation to AI summaries and models|

In each case, the mechanism is present, but the discipline around it is eroding. AI does not create this behavior, although it amplifies it by making the bypass easier and the output more convincing.

## The broader impact of generalized models

Another dimension to this problem is the nature of the models themselves. These systems are trained on broad datasets and optimized for general capability rather than specific organizational context. They’re extremely effective at recognizing patterns, but those patterns are not tailored to the nuances of individual environments. When we rely on them for decisions, designs, or implementations, we’re implicitly accepting that generalized behavior is sufficient. Most of the time it is. The risk emerges in edge cases, where context matters most and where the system has the least grounding.

## Regulation, responsibility and the gap between them

There are already attempts to address this at a regulatory level. Frameworks such as the EU AI Act and the NIST AI/RMF are beginning to define expectations around transparency, risk, and accountability. Already, though, legislation like this is seen as behind the curve and stifling innovation. While these are important steps, they operate at a different level than day-to-day system design. The gap between regulation and implementation is where most of the risk currently sits.

|Area|Current Direction|Practical Gap|
|---|---|---|
|Transparency|Disclosure of AI use in systems|Limited visibility into decision pathways|
|Accountability|Assignment of responsibility for outcomes|Diffuse responsibility across human and system|
|Risk Classification|Categorisation of high-risk AI use cases|Difficulty mapping real workflows to categories|
|Auditability|Requirement for traceable behaviour|Lack of detailed provenance in most systems|
|Data Governance|Controls on training and input data|Limited control over real time context usage|

Without stronger alignment between these layers, responsibility becomes difficult to assign. When something goes wrong, the question of who approved, who understood, and who was accountable becomes increasingly complex.

## A more honest model

We’re moving toward a world where humans supervise systems rather than directly control them. That shift is already underway, whether we acknowledge it or not. The question is how we design for it. Approval cannot remain a single click. Attribution cannot remain a line in a log. Trust cannot be assumed based on past success. We need systems that can explain their behavior, operate within enforceable boundaries, and provide meaningful provenance.

It means moving from the approval process of today to a more robust solution. Each element in the review process will have a series of yes/no and human and automated processes to get the approval understood, validated, and recorded.

![](https://extr-p-001.sitecorecontenthub.cloud/api/public/content/f7ba9e498c42415fb2fc09c8a70dfaaa?v=f783fccc) ![](https://extr-p-001.sitecorecontenthub.cloud/api/public/content/ef636840750540cf9d0b804f7740cd49?v=e6259201)

If we don’t, we risk creating environments where failures are difficult to trace and responsibility is impossible to assign. The tools will continue to improve, and they will continue to expand their role.

The challenge is ensuring that as they do, our models of control, approval, and accountability evolve with them, before “I approve” becomes the point at which responsibility quietly disappears.