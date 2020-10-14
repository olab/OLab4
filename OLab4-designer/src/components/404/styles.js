import styled from 'styled-components';

import { BLUE, DARK_GREY } from '../../shared/colors';

export const Wrapper = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  position: absolute;
  top: 50%;
  transform: translate(0, -50%);
  width: 100vw;
  text-align: center;

  > .link {
    text-decoration: none;
  }
`;

export const Header = styled.h1`
  font-size: 48px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: ${BLUE};
  margin: 2rem 0 0;
`;

export const Text = styled.h3`
  font-size: 20px;
  line-height: 24px;
  letter-spacing: 0.06em;
  color: ${DARK_GREY};
`;
