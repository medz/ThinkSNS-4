import { Args, GraphQLISODateTime, Mutation, Resolver } from '@nestjs/graphql';
import { HasTokenExpiredType } from 'src/authorization-token/enums';
import { Authorization } from 'src/authorization.decorator';
import { USER_NOT_SET_PHONE } from 'src/constants';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { SecuritySmsService } from './security-sms.service';

/**
 * Security SMS resolver.
 */
@Resolver()
export class SecuritySmsResolver {
  /**
   * Create the resolver.
   * @param securitySmsService Secyrity SMS service.
   */
  constructor(private readonly securitySmsService: SecuritySmsService) {}

  /**
   * Create a security SMS code for phone number.
   * @param phone Need create Security SMS code phone number.
   */
  @Mutation(() => GraphQLISODateTime, {
    description: 'Create a security SMS code for phone number',
  })
  async createSecurity(
    @Args({
      name: 'phone',
      description: 'Need send code phone number',
      type: () => String,
    })
    phone: string,
  ) {
    const { expiredAt } = await this.securitySmsService.send(phone);
    return expiredAt;
  }

  /**
   * Create the viewer security SMS code.
   * @param context Socfony app execution context.
   */
  @Mutation(() => GraphQLISODateTime, {
    description: 'Create the viewer security SMS code',
  })
  @Authorization({ hasAuthorization: true, type: HasTokenExpiredType.AUTH })
  async createViewerSecurity(@Context() context: ExecutionContext) {
    const { phone } = context.user || {};
    if (!phone) {
      throw new Error(USER_NOT_SET_PHONE);
    }

    return await this.createSecurity(phone);
  }
}
