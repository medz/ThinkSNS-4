import { ExecutionContext } from '@nestjs/common';
import { createParamDecorator } from '@nestjs/common';
import { GqlContextType, GqlExecutionContext } from '@nestjs/graphql';
import { Request } from 'express';
import { ExecutionContext as IContext } from './execution-context';

/**
 * Get Socfony execution context decorator.
 */
export const Context = createParamDecorator(function (
  _data: unknown,
  context: ExecutionContext,
): IContext {
  if (context.getType<GqlContextType>() === 'graphql') {
    return GqlExecutionContext.create(context).getContext();
  }

  return context.switchToHttp().getRequest<Request>().context;
});
