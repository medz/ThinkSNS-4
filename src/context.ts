import { PrismaClient } from "@prisma/client";

export interface Context {
    readonly prisma: PrismaClient;
}

const prisma = new PrismaClient();

export async function context(): Promise<Context> {
    return {
        prisma,
    };
}
