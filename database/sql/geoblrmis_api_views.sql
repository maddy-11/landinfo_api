-- Read-only views that present geoblrmis data in the column shape the API
-- already returns (the old MySQL `parcel`, `control_point` and `karam` tables).
-- Nothing is copied: the views read the live tables directly.
-- Geometry is stored in UTM 42N (32642) and served as WGS84 GeoJSON.

CREATE SCHEMA IF NOT EXISTS api;

DROP VIEW IF EXISTS api.parcel, api.control_point, api.karam;

CREATE OR REPLACE VIEW api.parcel AS
SELECT
    b.objectid                                   AS id,
    b.objectid                                   AS "OBJECTID",
    b.khasra_number                              AS "Khassra_No",
    NULL::varchar                                AS "Old_Khassr",
    NULL::varchar                                AS "Massavi_No",
    b.land_use_code                              AS "KhasraType",
    h.mouza_name                                 AS "Mauza_Name",
    h.circle_name                                AS "PC_Name",
    b.blrmis_khasra_id                           AS "KhassraId",
    NULL::bigint                                 AS "MozaId",
    NULL::varchar                                AS "JamaBandiY",
    h.hadbast_number                             AS "HadBastNo",
    h.name_ur                                    AS "MozaName_U",
    NULL::varchar                                AS "KhataNo",
    b.record_status                              AS "Status",
    NULL::varchar                                AS "UC",
    h.tehsil_name                                AS "Tehsil",
    h.district_name                              AS "District",
    h.division_name                              AS "Division",
    h.province_name                              AS "Province",
    b.shape_length                               AS "Shape_Leng",
    NULL::float8                                 AS "Shape_Le_1",
    b.shape_area                                 AS "Shape_Area",
    b.khasra_uid,
    b.recorded_area_sqm,
    ST_AsGeoJSON(ST_Transform(b.geom, 4326), 8) AS geometry,
    b.created_date                               AS created_at,
    b.last_edited_date                           AS updated_at
FROM cadastre_rural.rural_parcel_boundary b
-- Joined on the single mouza_uid key: the five-column composite join makes the
-- planner misestimate badly (13s for a DISTINCT on tehsil vs. ~100ms this way).
JOIN (
    SELECT h.*, m.hadbast_number, m.name_ur
    FROM master.v_rural_hierarchy h
    JOIN master.mouza m USING (province_code, division_code, district_code, tehsil_code, mouza_code)
    OFFSET 0
) h ON h.mouza_uid = concat_ws('-', b.province_code, b.division_code, b.district_code, b.tehsil_code, b.mouza_code)
UNION ALL
-- Test parcels (Kurram, Peshawar, Bajaur) that have no place in the client's
-- master hierarchy; kept separately in lrmis.parcel, already in API shape.
SELECT
    p.id, p."OBJECTID", p."Khassra_No", p."Old_Khassr", p."Massavi_No", p."KhasraType",
    p."Mauza_Name", p."PC_Name", p."KhassraId", p."MozaId", p."JamaBandiY", p."HadBastNo",
    p."MozaName_U", p."KhataNo", p."Status", p."UC", p."Tehsil", p."District", p."Division",
    p."Province", p."Shape_Leng", p."Shape_Le_1", p."Shape_Area",
    NULL::varchar, NULL::numeric,
    p.geometry, p.created_at, p.updated_at
FROM lrmis.parcel p;

CREATE OR REPLACE VIEW api.control_point AS
SELECT
    g.objectid                                         AS id,
    substring(g.gcp_level FROM '[0-9]+')::int          AS level,
    g.bm_code,
    g.lims_id,
    g.district_src                                     AS "District",
    g.tehsil_src                                       AS "Tehsil",
    g.status_shapefile                                 AS status_raw,
    CASE
        WHEN g.status_shapefile ILIKE 'halt%' OR g.access_class = 'SECURITY_RISK' THEN 'blocked'
        WHEN g.monument_status = 'COMPLETE'
          OR g.status_shapefile ILIKE 'complete%'
          OR g.status_shapefile ILIKE 'construct%'                                 THEN 'constructed'
        WHEN g.status_shapefile ILIKE 'not%start%'                                 THEN 'not_started'
        WHEN g.monument_status = 'IN_PROCESS'                                      THEN 'in_progress'
        ELSE 'other'
    END                                                AS status,
    -- Test points imported from MySQL keep their original free-text fields and
    -- exact coordinates in lrmis.control_point_extra (no client column for them).
    CASE WHEN x.bm_code IS NOT NULL THEN x.execution ELSE g.consultant END AS execution,
    x.within_10k,
    CASE WHEN x.bm_code IS NOT NULL THEN x.distance
         WHEN g.alt_distance_m > 0 THEN round(g.alt_distance_m)::text || 'm' END AS distance,
    NULLIF(g.alt_location, '')                         AS alt_location,
    COALESCE(x.original_x, g.src_lon::numeric(12,8))   AS original_x,
    COALESCE(x.original_y, g.src_lat::numeric(12,8))   AS original_y,
    -- "Altered" only when the monument was moved off its source position.
    CASE WHEN x.bm_code IS NOT NULL THEN x.altered_x
         WHEN g.src_lon IS NULL
           OR NOT ST_DWithin(ST_Transform(ST_SetSRID(ST_MakePoint(g.src_lon, g.src_lat), 4326), 32642), g.geom, 1)
         THEN ST_X(ST_Transform(g.geom, 4326))::numeric(12,8) END AS altered_x,
    CASE WHEN x.bm_code IS NOT NULL THEN x.altered_y
         WHEN g.src_lon IS NULL
           OR NOT ST_DWithin(ST_Transform(ST_SetSRID(ST_MakePoint(g.src_lon, g.src_lat), 4326), 32642), g.geom, 1)
         THEN ST_Y(ST_Transform(g.geom, 4326))::numeric(12,8) END AS altered_y,
    CASE WHEN x.bm_code IS NOT NULL THEN x.remarks ELSE NULLIF(g.remarks, '') END AS remarks,
    ST_AsGeoJSON(ST_Transform(g.geom, 4326), 8)       AS geometry,
    g.created_date                                     AS created_at,
    g.last_edited_date                                 AS updated_at
FROM survey_control.gcp_benchmark g
LEFT JOIN lrmis.control_point_extra x ON x.bm_code = g.bm_code;

CREATE OR REPLACE VIEW api.karam AS
SELECT
    k.objectid                                   AS id,
    k.objectid                                   AS "OBJECTID_1",
    k.karam::varchar                             AS "Karam",
    NULL::varchar                                AS "Karam_Urdu",
    k.khasra_number                              AS "Khasra_No_",
    k.village_name                               AS "Mauza_Name",
    k.tehsil_src                                 AS "Tehsil_Nam",
    k.length_m                                   AS "SHAPE_Leng",
    ST_AsGeoJSON(ST_Transform(k.geom, 4326), 8) AS geometry,
    k.geom
FROM cadastre_rural.rural_karam_dimension k;
