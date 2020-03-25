# Third Coast International Audio Festival

## Database

Data is a saved and modeled as a [graph](https://en.wikipedia.org/wiki/Graph_(abstract_data_type))
The database is a document consisting of vertices and edges. All objects in the site descend from a common type (read: the same thing), and then depending on properties and context, they mean things like "feature", "person".

### Vertices

A vertex is just a type of node tracked in the xml file. Again, they have identical structure but meaning takes shape around particular use of a vertex and its edges. All meaning is defined in application data models that are responsible for figuring out what edges-vertex relationships ought to mean.

#### Types of vertices

Despite all being of the same type, a vertex can be grouped for faster data retrieval and generally keeping things a bit more organized. The groups are thus:

- person
- feature
- collection
- organization
- event (conference and festival are just special cases of events)
- competition

### Edges

Edges represent a connection from one vertex to another. For example, *person* vertex may have edges pointing to a *feature* vertices as , using the edge category *producer*

#### Edge (connection) Categories

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


From the previous example, you can describe a great deal based on context, without the cumbersome schema of a database. The role of sponsorship is broad, but can be articulated many ways without creating any special rules in the CMS

- *Vertex:Organization*--**Edge:Sponsor**--*Vertex:Competition*  representing sponsors of specific competitions
- *Vertex:Organization*--**Edge:Sponsor**--*Vertex:Event* representing sponsors of any event
- *Vertex:Person*--**Edge:Sponsor**--*Vertex:Organization* representing donorship to the organization
- *Vertex:Organization*--**Edge:Sponsor**--*Vertex:Organization* representing funder of the organization




## Preview and Archive Mode

A competitions and conferences have two states, and those states are determined by the **date** field. A date landing in the future will compose a competition page in *preview mode*, which indicates it will have a banner and any associated articles linked and organized in the main content area. (TODO: create video). When a date falls in the past, it will enter archive mode, which will highlight winners instead.
