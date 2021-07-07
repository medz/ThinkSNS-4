import { ApolloServer } from "apollo-server";
import { context } from "./runtime";
import { schema } from "./schema";

export const server = new ApolloServer({
    schema,
    context,
});
