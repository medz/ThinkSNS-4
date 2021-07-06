import { idArg, nonNull, nullable, queryField } from "nexus";
import { Context } from "../context";
import { UserEntity } from "./user.entity";

const args = {
    id: nonNull(idArg({
        description: "当前查询的用户 ID"
    }))
};

export const FindOneUserQuery = queryField('user', {
    args,
    description: "使用 ID 查询一个用户",
    type: nullable(UserEntity),
    resolve(_source, { id }, { prisma }: Context) {
        return prisma.user.findUnique({
            where: { id },
            rejectOnNotFound: false,
        });
    },
});