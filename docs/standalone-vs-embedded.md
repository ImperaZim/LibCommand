
# Standalone vs Embedded

`LibCommand` can be used as an independent PocketMine-MP library and may also be available through EasyLibrary's embedded distribution.

## Use the standalone library when

- you want this library to own its runtime commands, tasks, listeners or managers;
- you want to update this library independently from EasyLibrary;
- another plugin explicitly depends on the standalone PHAR.

## Use the EasyLibrary embedded copy when

- your plugin only needs the public API namespace;
- you want fewer separate PHARs on the server;
- fallback runtime behavior is enough for the target use case.

## Important

Do not register the same runtime feature twice. If the standalone library is installed, treat it as the primary runtime and let EasyLibrary act as fallback/compatibility where supported.
