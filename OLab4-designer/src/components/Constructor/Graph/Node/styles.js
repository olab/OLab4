import styled from 'styled-components';

export const ForeignObject = styled.foreignObject`
  pointer-events: ${({ isDisabled }) => (isDisabled ? 'none' : 'auto')};
  opacity: ${({ isDisabled }) => (isDisabled ? 0.6 : 1)};
`;

export default {
  ForeignObject,
};
