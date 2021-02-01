import { ArgsType, Field, PickType } from '@nestjs/graphql';
import { CreateAuthorizationTokenArgs } from 'src/authorization-token/dto/create-authorization-token.args';
import { UserUpdateInput } from './user-update.input';

/**
 * Update viewer args.
 */
@ArgsType()
export class UpdateViewerArgs extends PickType(
  CreateAuthorizationTokenArgs,
  ['security', 'type'] as const,
  ArgsType,
) {
  /**
   * User update input
   */
  @Field(() => UserUpdateInput, {
    description: 'User update input',
  })
  data: UserUpdateInput;

  /**
   * Update phone need set new phone security code
   */
  @Field(() => String, {
    nullable: true,
    description: 'Update phone need set new phone security code',
  })
  newPhoneSecurity?: string;
}
