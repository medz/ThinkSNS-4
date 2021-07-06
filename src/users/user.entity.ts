import { objectType } from "nexus";
import { User } from "nexus-prisma";

/**
 * User entity
 */
export const UserEntity = objectType({
    name: User.$name,
    description: User.$description,
    definition(t) {
        t.field(User.id);
        t.field(User.username);
    },
});
