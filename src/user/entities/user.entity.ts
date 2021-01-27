import { ObjectType } from '@nestjs/graphql';
import { UserInterface } from './user.interface';

/**
 * User entity.
 */
@ObjectType({
  implements: [UserInterface],
  description: 'User entity',
})
export class UserEntity extends UserInterface {}
