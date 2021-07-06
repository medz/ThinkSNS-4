import { ApolloServer } from "apollo-server";
import { schema } from "./schema";

export const server = new ApolloServer({ schema });
