import { PrismaClient } from "@prisma/client";
import { prisma } from "./prisma";

export interface Context {
    readonly prisma: PrismaClient;
}

export async function contextFactory(): Promise<Context> {
    return {
        prisma: prisma,
    };
}
