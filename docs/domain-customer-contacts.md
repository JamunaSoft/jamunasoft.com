# Domain customer contacts

New registrations use the customer's contact details for registrant, administrative, technical and billing contacts. Website Settings no longer supplies a registrant contact. Transfers retain the existing domain owner's contact from the source registrar; renewals retain the existing registrar contact.

The order stores a contact snapshot in `meta.registrant`: first and last name, email, phone, street address, city, postal code and ISO country code, with optional organization, second address line and state/province. Enter international phone numbers as `+880.1700000000`. Fill state/province when required for the customer's country. Registrar validation may impose country or extension-specific requirements.

In Domain Orders, choose a panel customer to prefill their details, then review and complete them. Existing full names are split at the first space for prefilling; the first and last name fields remain editable. Public checkout accepts a complete new contact or a saved billing contact owned by the logged-in customer. Incomplete saved contacts must be completed or replaced before placing an order.

For an existing failed registration, use **Edit customer contact**, save the customer's full details, then **Retry**. This only changes the pending order; completed registrations cannot be edited with this action. Account changes do not overwrite the order's saved contact snapshot.

Spaceship creates/deduplicates a contact from the order snapshot. ResellCube resolves a customer account by the customer's email and caches contact IDs by reseller account, customer ID and contact contents; it never reuses the former global house contact cache.

Provider references: [Spaceship contact API](https://docs.spaceship.dev/), [ResellCube/LogicBoxes contact fields](https://manage.resellerclub.com/kb/answer/790).
