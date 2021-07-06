import { makeSchema } from "nexus";
import NexusPrismaScalars from 'nexus-prisma/scalars'

import * as Users from './users';

export const schema = makeSchema({
    types: [
        NexusPrismaScalars,
        Users,
    ],
});