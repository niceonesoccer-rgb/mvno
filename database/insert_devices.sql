-- 단말기 데이터 삽입
USE `mvno_db`;

-- 제조사 ID 가져오기 (변수 사용 불가하므로 직접 ID 사용)
-- 삼성: 1, 애플: 2, 샤오미: 3

-- Galaxy S23 Series (2023 출시)
-- Galaxy S23
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(1, 'Galaxy S23', '256GB', 1155000, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active'),
(1, 'Galaxy S23', '512GB', 1353000, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active');

-- Galaxy S23+
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(1, 'Galaxy S23+', '256GB', 1397000, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active'),
(1, 'Galaxy S23+', '512GB', 1595000, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active');

-- Galaxy S23 Ultra
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(1, 'Galaxy S23 Ultra', '256GB', 1599400, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active'),
(1, 'Galaxy S23 Ultra', '512GB', 1720400, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active'),
(1, 'Galaxy S23 Ultra', '1TB', 1984400, '팬텀 블랙, 그린, 라벤더, 크림', '2023-02-01', 'active');

-- iPhone 16 Series (2024 출시)
-- iPhone 16
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(2, 'iPhone 16', '128GB', 1250000, '블랙, 화이트, 블루, 핑크, 그린', '2024-09-01', 'active'),
(2, 'iPhone 16', '256GB', 1390000, '블랙, 화이트, 블루, 핑크, 그린', '2024-09-01', 'active'),
(2, 'iPhone 16', '512GB', 1640000, '블랙, 화이트, 블루, 핑크, 그린', '2024-09-01', 'active');

-- iPhone 16 Plus
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(2, 'iPhone 16 Plus', '128GB', 1350000, '블랙, 화이트, 블루, 핑크, 그린', '2024-09-01', 'active'),
(2, 'iPhone 16 Plus', '256GB', 1490000, '블랙, 화이트, 블루, 핑크, 그린', '2024-09-01', 'active'),
(2, 'iPhone 16 Plus', '512GB', 1740000, '블랙, 화이트, 블루, 핑크, 그린', '2024-09-01', 'active');

-- iPhone 16 Pro
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(2, 'iPhone 16 Pro', '128GB', 1550000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active'),
(2, 'iPhone 16 Pro', '256GB', 1690000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active'),
(2, 'iPhone 16 Pro', '512GB', 1940000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active'),
(2, 'iPhone 16 Pro', '1TB', 2190000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active');

-- iPhone 16 Pro Max
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(2, 'iPhone 16 Pro Max', '256GB', 1900000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active'),
(2, 'iPhone 16 Pro Max', '512GB', 2150000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active'),
(2, 'iPhone 16 Pro Max', '1TB', 2400000, '티타늄 블랙, 티타늄 화이트, 티타늄 블루, 내추럴 티타늄', '2024-09-01', 'active');

-- iPhone 15 Series (2023 출시)
-- iPhone 15
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(2, 'iPhone 15', '128GB', 1250000, '블루, 핑크, 옐로우, 그린, 블랙', '2023-09-01', 'active'),
(2, 'iPhone 15', '256GB', 1390000, '블루, 핑크, 옐로우, 그린, 블랙', '2023-09-01', 'active'),
(2, 'iPhone 15', '512GB', 1640000, '블루, 핑크, 옐로우, 그린, 블랙', '2023-09-01', 'active');

-- Xiaomi 13 Series (2023 한국 출시)
-- Xiaomi 13
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(3, 'Xiaomi 13', '256GB', 1099000, '화이트, 블랙', '2023-01-01', 'active');

-- Xiaomi 13 Pro
INSERT INTO `devices` (`manufacturer_id`, `name`, `storage`, `release_price`, `color`, `release_date`, `status`) VALUES
(3, 'Xiaomi 13 Pro', '256GB', 1399000, '세라믹 화이트, 세라믹 블랙', '2023-01-01', 'active');

