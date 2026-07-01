# Contributing

## Development flow

1. Create a feature branch from `main`.
2. Keep changes focused on one topic.
3. Run the package build before opening a pull request:

```bash
python3 build/package.py
```

4. Open a pull request and describe the Joomla area affected by the change.

## Code style

- Follow the existing Joomla MVC structure and namespacing.
- Keep component, module and plugin manifests in sync with file layout.
- Do not commit generated ZIP files from `dist/`.

