# Third Coast International Audio Festival

## Data is a saved and modeled as a [graph](https://en.wikipedia.org/wiki/Graph_(abstract_data_type))
The database is a document consisisting of vertices and edges. All objects in the site descend from a common type (read: the same thing), and then depending on properties and context, they mean things like "feature", "person".

### A Vertex

A vertex is just a type of node tracked in the xml file. Again, they have identical structure but meaning takes shape around particular use of a vertex and its edges. All meaning is defined in application data models that are responsible for figuring out what edges-vertex relationships ought to mean.

#### Types of vertices

Despite all being of the same type, a vertex can be grouped for faster data retrieval and generally keeping things a bit more organized. The groups are thus:

- person
- published
- organization
- event
- conference
- festival
- competition

### Edges

Edges represent a connection from one vertex to another. A edge is a child node of a vertex that references another (hopefully different) vertex. In this way it is directed, as the parent node becomes the 'from' and the referenced node becomes the 'to'.

#### Types of edges.

Edges for TCIAF have a specified type and with this, they provide the necessary context to create a full-featured page based on several independent vertices via their edge connections. Types of edges are thus:

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

The above list of edges represents a relationship between two vertices. A 'person' vertex can have a child edge pointing to a 'published' vertex, using the edge type 'producer'. Confusion could arise, because a 'published' vertex could have a child edge pointing to a 'person' vertex as well (and that might even be warranted sometimes). However, to keep things generally organized, try to follow the protocol '**type** of edge' *of* '**vertex** identity'. IE. *Curator of event*, *Staff of Organization*, *Winner of Competition*. Could sometimes be easier to think of as 'belongs to', but any label as such will not apply to all edge cases.

From the previous example, you can describe a great deal based on context, without the cumbersome schema of a database. The role of sponsorship is broad, and further

- Sponsor (organization) of *Competition*
- Sponsor (organization) of *Event*
- Sponsor (person) of *Organization*
