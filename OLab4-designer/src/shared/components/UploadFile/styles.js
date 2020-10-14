import styled from 'styled-components';

import { GREEN } from '../../colors';

export const UploadButton = styled.span`
  cursor: pointer;
  color: ${GREEN};
  display: inline-flex;
  align-items: center;
  font-family: SF Pro Display;
  font-size: 14px;
  line-height: 17px;
  letter-spacing: 0.06em;
  white-space: nowrap;

  > svg {
    margin-right: 10px;
  }
`;

export default {
  UploadButton,
};
