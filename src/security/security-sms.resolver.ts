import { Args, GraphQLISODateTime, Mutation, Resolver } from '@nestjs/graphql';
import { SecuritySmsService } from './security-sms.service';

@Resolver()
export class SecuritySmsResolver {
  constructor(private securitySmsService: SecuritySmsService) {}

  @Mutation(() => GraphQLISODateTime)
  async createSecuritySms(
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
}
