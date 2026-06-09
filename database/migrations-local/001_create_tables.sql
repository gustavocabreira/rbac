-- LOCAL/TEST ONLY — production uses the legacy schema. Never run this in production.

CREATE TABLE IF NOT EXISTS `tb_Modules` (
    `modModuleID`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `modModuleSlug` VARCHAR(100) NOT NULL,
    `modModuleName` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`modModuleID`),
    UNIQUE KEY `uq_modules_slug` (`modModuleSlug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Module_Roles` (
    `mroModuleRoleID`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mroModuleRoleSlug`  VARCHAR(100) NOT NULL,
    `mroModuleRoleName`  VARCHAR(255) NOT NULL,
    `mroModuleRoleLevel` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`mroModuleRoleID`),
    UNIQUE KEY `uq_module_roles_slug` (`mroModuleRoleSlug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Permissions` (
    `perPermissionID`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `perPermissionModuleID` INT UNSIGNED NOT NULL,
    `perPermissionName`     VARCHAR(255) NOT NULL,
    `perPermissionSlug`     VARCHAR(255) NOT NULL,
    PRIMARY KEY (`perPermissionID`),
    UNIQUE KEY `uq_permissions_slug` (`perPermissionSlug`),
    KEY `idx_permissions_module` (`perPermissionModuleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Module_Role_Permissions` (
    `mrpModuleRolePermissionID`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mrpModuleRolePermissionRoleID`       INT UNSIGNED NOT NULL,
    `mrpModuleRolePermissionPermissionID` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`mrpModuleRolePermissionID`),
    UNIQUE KEY `uq_mrp_role_perm` (`mrpModuleRolePermissionRoleID`, `mrpModuleRolePermissionPermissionID`),
    KEY `idx_mrp_role` (`mrpModuleRolePermissionRoleID`),
    KEY `idx_mrp_perm` (`mrpModuleRolePermissionPermissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Resource_Agent_Access` (
    `raaResourceAgentAccessID`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `raaResourceAgentAccessCompanyID`    INT UNSIGNED NOT NULL,
    `raaResourceAgentAccessAgentID`      INT UNSIGNED NOT NULL,
    `raaResourceAgentAccessModuleID`     INT UNSIGNED NOT NULL,
    `raaResourceAgentAccessResourceID`   INT UNSIGNED NOT NULL,
    `raaResourceAgentAccessModuleRoleID` INT UNSIGNED NOT NULL,
    `raaResourceAgentAccessGrantedBy`    INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`raaResourceAgentAccessID`),
    UNIQUE KEY `uq_raa_company_agent_module_resource` (
        `raaResourceAgentAccessCompanyID`,
        `raaResourceAgentAccessAgentID`,
        `raaResourceAgentAccessModuleID`,
        `raaResourceAgentAccessResourceID`
    ),
    KEY `idx_raa_agent_module_resource` (
        `raaResourceAgentAccessAgentID`,
        `raaResourceAgentAccessModuleID`,
        `raaResourceAgentAccessResourceID`
    ),
    KEY `idx_raa_company` (`raaResourceAgentAccessCompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Resource_Role_Access` (
    `rraResourceRoleAccessID`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rraResourceRoleAccessCompanyID`    INT UNSIGNED NOT NULL,
    `rraResourceRoleAccessRoleID`       INT UNSIGNED NOT NULL,
    `rraResourceRoleAccessModuleID`     INT UNSIGNED NOT NULL,
    `rraResourceRoleAccessResourceID`   INT UNSIGNED NOT NULL,
    `rraResourceRoleAccessModuleRoleID` INT UNSIGNED NOT NULL,
    `rraResourceRoleAccessGrantedBy`    INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`rraResourceRoleAccessID`),
    UNIQUE KEY `uq_rra_company_role_module_resource` (
        `rraResourceRoleAccessCompanyID`,
        `rraResourceRoleAccessRoleID`,
        `rraResourceRoleAccessModuleID`,
        `rraResourceRoleAccessResourceID`
    ),
    KEY `idx_rra_role_module_resource` (
        `rraResourceRoleAccessRoleID`,
        `rraResourceRoleAccessModuleID`,
        `rraResourceRoleAccessResourceID`
    ),
    KEY `idx_rra_company` (`rraResourceRoleAccessCompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Roles` (
    `rolRoleID`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rolRoleCompanyID`   INT UNSIGNED NOT NULL,
    `rolRoleName`        VARCHAR(255) NOT NULL,
    `rolRoleDescription` TEXT NULL,
    `rolRoleStatus`      TINYINT(1) NOT NULL DEFAULT 1,
    `rolRoleCreatedBy`   INT UNSIGNED NULL DEFAULT NULL,
    `rolRoleUpdatedBy`   INT UNSIGNED NULL DEFAULT NULL,
    `rolRoleDeletedBy`   INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`rolRoleID`),
    KEY `idx_roles_company` (`rolRoleCompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_Companies_Agents` (
    `coaCompaniesAgentsCompanyID` INT UNSIGNED NOT NULL,
    `coaCompaniesAgentsAgentID`   INT UNSIGNED NOT NULL,
    `coaCompaniesAgentsAgentRole` INT UNSIGNED NOT NULL,
    KEY `idx_coa_company_agent_role` (
        `coaCompaniesAgentsCompanyID`,
        `coaCompaniesAgentsAgentID`,
        `coaCompaniesAgentsAgentRole`
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
