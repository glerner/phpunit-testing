# AI Context Window and Memory

## Memory Optimization

After our cleanup, we've kept only 3 memories with a total of approximately 700-750 words:

- Memory ID: b20e4f77 - User Preferences for WordPress Plugin Development (~120 words)
- Memory ID: 34e89287 - WordPress Coding Standards Priority (~400 words, expanded with merged content)
- Memory ID: 611d425f - Code Inventory and Code Quality Standards (~200 words)

This is a significant reduction from the original ~3,300 words across 23 memories.

## AI Context Window Size

The exact context window size varies by model, but for Claude 3.7 Sonnet, the context window is approximately 180,000 tokens. A token is roughly 3/4 of a word in English, so that's about 135,000 words.

With only ~750 words of memories, we have plenty of room for code-inventory.md and other important files. The memories now consume less than 1% of the available context window, leaving ample space for:

- The content of code-inventory.md
- Other relevant code files needed for your tasks
- Conversation history
- Any additional documentation you might need to reference

This streamlined approach means the AI can focus more effectively on your actual code and requirements rather than being constrained by excessive memory content.

## Cascade Prompt Additions

When submitting a prompt to Claude 3.7 Sonnet, several types of context are added to your prompt:

- **System Instructions**: Approximately 1,000-2,000 tokens (roughly 750-1,500 words) of system instructions that define behavior, capabilities, and limitations.

- **Memories**: Currently about 750 words (~1,000 tokens) after our cleanup.

- **Tool Definitions**: Around 1,000 to 2,000 tokens describing the available tools the AI can use.

- **Conversation History**: Variable, but typically 5,000 to 10,000 tokens for a medium-length conversation.

In total, before you even type your prompt, approximately 8,000 to 15,000 tokens (6,000 to 11,000 words) might be used for these contextual elements, leaving around 165,000 to 172,000 tokens for your actual prompt, code files, and AI responses.

## Code Inventory with AI

Based on the code-inventory-strategy.md file, organizing code inventory by functional groups provides several benefits:

### Benefits of Functional Group Organization

1. **Focused Development**: Breaking down the inventory by functional groups (UI, Core Color Manipulation, Palette Management, etc.) allows you to focus on one cohesive area at a time, aligning with the "Modular Development" core principle.

2. **Structured Documentation**: The three-part structure (classes, methods, constants) for each functional group provides comprehensive context without overwhelming with irrelevant details.

3. **Clear Relationships**: Documenting class relationships and dependencies helps understand how components interact, even when focusing on just one functional group.

4. **Implementation Workflow**: The "UI-First Development" approach with stub implementations makes it easier to work incrementally while maintaining a clear vision of the overall system.

### Context Switching Between Functional Groups

Changing context between functional groups is straightforward to implement:

1. **Loading Context**: When working on a specific functional group, load the three inventory files for that group:
   ```
   /docs/code-inventory/[group]-classes.md
   /docs/code-inventory/[group]-methods.md
   /docs/code-inventory/[group]-constants.md
   ```

2. **Context Switching**: Create a simple command or prompt template like:
   ```
   "Load context for [Group Name] functional group, unloading other group context"
   ```
   This triggers loading the relevant inventory files while unloading others.

3. **Memory Management**: Since each functional group's documentation is modular and self-contained, swapping them in and out of the context window is clean and efficient.

4. **Boundary Handling**: The approach of documenting relationships between classes helps manage cases where you need to understand interactions across functional groups.

### Benefits for Large Projects

This approach is particularly well-suited for a large project like the Color Palette Generator because:

- It keeps the context focused and relevant
- It aligns with MVC architecture
- It makes efficient use of the context window
- It supports incremental development workflow

The total context for one functional group (3 files) would likely be around 5,000-10,000 tokens, leaving plenty of room for actual code, conversation, and other necessary context.
