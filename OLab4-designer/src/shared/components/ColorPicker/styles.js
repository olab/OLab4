import styled from 'styled-components';

import { GREY, BLUE_GREY } from '../../colors';

export const ColorPickerWrapper = styled.div`
  display: flex;
  > label {
    margin-right: 0.5rem;
    align-self: center;
  }
  > div {
    position: relative;
  }
`;

export const LinkColorItem = styled.div`
  width: 1.5rem;
  height: 1.5rem;
  background: ${({ color }) => color || BLUE_GREY};
  border-radius: 50%;
  box-shadow: 0 0 5px 0 ${GREY};
  cursor: pointer;
`;

export const GithubPickerWrapper = styled.div`
  position: absolute;
  z-index: 1;
  top: calc(100% + 12px);
  transform: translate(-50%, 0);

  .github-picker {
    > div {
      &:first-of-type {
        left: calc(50% + 4px) !important;
      }
      &:last-of-type {
        left: calc(50% + 5px) !important;
      }
    }
  }
`;
