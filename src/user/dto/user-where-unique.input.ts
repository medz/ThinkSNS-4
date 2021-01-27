import { Field, ID, InputType } from "@nestjs/graphql";
import { Prisma } from "@prisma/client";

@InputType()
export class UserWhereUniqueInput implements Prisma.UserWhereUniqueInput {
    @Field(() => ID, {
        description: "User ID",
        nullable: true,
    })
    id?: string;

    @Field(() => String, {
        description: "User login name",
        nullable: true,
    })
    login?: string;

    @Field(() => String, {
        description: "User email",
        nullable: true,
    })
    email?: string;

    @Field(() => String, {
        description: "User phone number",
        nullable: true,
    })
    phone?: string;
}