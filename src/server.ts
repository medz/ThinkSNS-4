import { ApolloServer } from "apollo-server";
import { contextFactory } from "./runtime";
import { schema } from "./schema";

// Define a Apollo Server with a schema and context
// exports the server
export const server = new ApolloServer({
  schema,
  context: contextFactory
});
