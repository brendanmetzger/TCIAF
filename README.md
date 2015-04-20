# Third Coast International Audio Festival

### Tokens

A token is just a type of node tracked in the xml file. They have identical structure but the particulars and differences are provided through context and use, not through additional record keeping. Context and use are defined in application data Models, which are primarily responsible for organizing pointers/token associations, which again, control context to a large degree.

#### Types of tokens
- person
- published
- unpublished
- organization
- event
- conference
- festival
- competition

### Pointers

#### Types of pointers.

A pointer is a child node of a token that references another (hopefully different) token.

- winner
- presenter
- curator
- judge
- staff
- friend
- producer
- board
- sponsor
- issue
- participant

The above list of pointers represents a relationship between two tokens. A 'person' token can point to a 'published' token, using the pointer type 'producer'. Confusion could arise, because a 'published' token could point to a 'person' token under the same criteria - and the document parser would validate that. However, to keep things organized, try to follow tho protocol '**type** of pointer' *of* '**token** identity'. IE. *Curator of event*, *Staff of Organization*, *Winner of Competition*.

From the previous example, you can describe a great deal based on context, without the cumbersome schema of a database. The role of sponsorship is broad, and further

- Sponsor of *Competition*
- Sponsor of *Event*
- Sponsor of *Conference*
- Sponsor of *Organization* (a sponsor could be a person depending on context!)



