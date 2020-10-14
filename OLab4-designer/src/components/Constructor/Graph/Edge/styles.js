import styled from 'styled-components';
import { BLUE_GREY } from '../../../../shared/colors';

export const EdgeWrapper = styled.g`
  color: ${BLUE_GREY};
  stroke: ${BLUE_GREY};
  stroke-width: 5px;

  & > use {
    stroke: none;
    marker-end: url(#end-arrow);
    cursor: ${({ isLinkingStarted }) => (isLinkingStarted ? 'inherit' : 'pointer')};
    pointer-events: all;
  }
`;

export default {
  EdgeWrapper,
};
