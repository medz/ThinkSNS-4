import { Field, ObjectType } from '@nestjs/graphql';
import { User } from '@socfony/prisma';
import { UserInterface } from './user.interface';

/**
 * Viewer entity.
 */
@ObjectType({
  implements: [UserInterface],
  description: 'Viewer entity.',
})
export class ViewerEntity
  extends UserInterface
  implements Omit<User, 'password'> {
  /**
   * User bound E-Mail address.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User bound E-Mail address.',
  })
  email: string;

  /**
   * User bound Phone full number.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User bound Phone full number.',
  })
  phone: string;
}
