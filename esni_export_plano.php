<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Exportar Reporte Operacional ESNI a Excel (.xlsx) - PLANO HORIZONTAL
 *
 * Genera un archivo Excel usando como base la plantilla "Operacional.xlsx"
 * (ubicada en uploads/Operacional.xlsx) y llenando las celdas de la fila 28
 * con los datos de la Seccion A (Menores de 01 anio), Seccion B (De 01 anio),
 * Seccion C (Mayores de 01 anio), Seccion D (De 03 anios), Seccion E1
 * (De 04 anios), Seccion E2 (De 05 - 07 anios), Seccion F
 * (dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS), Seccion F2
 * (dT EN GESTANTES POR GRUPO DE EDAD 10-59 ANIOS), Seccion G
 * (dT ADULTO EN VARONES EN RIESGO POR GRUPO DE EDAD), Seccion H
 * (INFLUENZA ESTACIONAL EN OTROS GRUPOS), Seccion J
 * (POBLACION DE 05 A 59 ANIOS: VACUNACION CONTRA LA HEPATITIS B), Seccion K
 * (ANTIAMARILICA EN POBLACION NO VACUNADA Y VIAJEROS A ZONAS ENDEMICAS),
 * Seccion L (SOLO GESTANTES (dtpa)), Seccion N (VACUNA VPH:
 * femenino y masculino, dosis unica), Seccion O (NEUMOCOCO EN POBLACION
 * EN RIESGO), Seccion P (DT-DOSIS ADICIONALES), Seccion Q (VARICELA
 * POR GRUPO DE EDAD / RIESGO), Seccion R (HEPATITIS A POR GRUPO DE
 * EDAD / RIESGO) y Seccion T (SPR-SARAMPION POR GRUPO DE EDAD /
 * RIESGO) del reporte ESNI.
 *
 * Mapeo de celdas (plantilla Operacional.xlsx):
 *
 *   ENCABEZADO (filtros seleccionados por el usuario):
 *     B5  = Mes (nombre, ej: "Enero")      <- dato del select "mes"
 *     C28 = Establecimiento (nombre)        <- dato del select "establecimiento"
 *
 *   Seccion A - fila 28 (Casos por vacuna/dosis, menores de 01 anio):
 *
 *   BCG:
 *     E28 = BCG - 24 HORAS                       (Casos)
 *     F28 = BCG - 28 DIAS                        (Casos)
 *     G28 = BCG - DE 01M A 11M 29D               (Casos)
 *
 *   HEPATITIS VIRAL B:
 *     I28 = HEPATITIS VIRAL B - 12 HORAS         (Casos)
 *     J28 = HEPATITIS VIRAL B - 24 HORAS         (Casos)
 *
 *   ANTIPOLIO - IPV:
 *     QA28 = ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS   (Casos)
 *     QB28 = ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS   (Casos)
 *     QC28 = ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS        (Casos)
 *
 *   PENTAVALENTE:
 *     Q28  = PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS  (Casos)
 *     R28  = PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS  (Casos)
 *     S28  = PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS  (Casos)
 *
 *   ROTAVIRUS:
 *     AF28 = ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS         (Casos)
 *     AG28 = ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS         (Casos)
 *
 *   NEUMOCOCO:
 *     AJ28 = NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS         (Casos)
 *     AK28 = NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS         (Casos)
 *
 *   INFLUENZA:
 *     AN28 = INFLUENZA - 06 Y 07 MESES - 1RA DOSIS         (Casos)
 *     AO28 = INFLUENZA - 06 Y 07 MESES - 2RA DOSIS         (Casos)
 *
 *   Seccion B - fila 28 (Casos por vacuna/dosis, de 01 anio):
 *
 *     AX28 = 1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS  (Casos)
 *     AY28 = 1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS        (Casos)
 *     AZ28 = 1A 11M 29D - DOSIS UNICA - INFLUENZA          (Casos)
 *     BA28 = VARICELA 1RA                                  (Casos)
 *     BB28 = NEUMOCOCO 1RA                                 (Casos)
 *     BC28 = NEUMOCOCO 2DA                                 (Casos)
 *     BF28 = 15 MESES - ANTIAMARILICA - DOSIS UNICA        (Casos)
 *     BG28 = 18 MESES - SPR - 2DA DOSIS                    (Casos)
 *     BH28 = 18 MESES - REF. DPT - 1RA DOSIS               (Casos)
 *     BI28 = 18 MESES - REF. IPV                           (Casos)
 *     BQ28 = No vacunado PENTAVALENTE 2da                  (Casos)
 *     BR28 = No vacunado PENTAVALENTE 3ra                  (Casos)
 *     TG28 = 15 MESES - HEPATITIS A - DOSIS UNICA          (Casos)
 *     TH28 = No vacunado IPV                               (Casos)
 *     TV28 = 18 MESES - REF. PENTAVALENTE                  (Casos)
 *
 *   Seccion C - fila 28 (Casos por vacuna/dosis, Mayores de 01 anio):
 *
 *   INFLUENZA / NEUMOCOCO CON/SIN COMORBILIDAD:
 *     CF28 = INFLUENZA CON COMORBILIDAD - 1RA DOSIS        (Casos)
 *     CG28 = INFLUENZA SIN COMORBILIDAD - 1RA DOSIS        (Casos)
 *     CH28 = NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS        (Casos)
 *
 *   VACUNACION NO OPORTUNA - NEUMOCOCO:
 *     CI28 = VACUNACION NO OPORTUNA - NEUMOCOCO D1         (Casos)
 *     CJ28 = VACUNACION NO OPORTUNA - NEUMOCOCO D2         (Casos)
 *     CK28 = VACUNACION NO OPORTUNA - NEUMOCOCO D3         (Casos)
 *
 *   ANTIAMARILICA:
 *     CN28 = ANTIAMARILICA - 1RA DOSIS                     (Casos)
 *
 *   VACUNACION NO OPORTUNA - ANTIPOLIO - IPV:
 *     CO28 = VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 1RA DOSIS  (Casos)
 *     CP28 = VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 2DA DOSIS  (Casos)
 *     TI28 = VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 3RA DOSIS  (Casos)
 *
 *   VACUNACION NO OPORTUNA - PENTAVALENTE:
 *     CU28 = VACUNACION NO OPORTUNA - PENTAVALENTE - 1RA DOSIS    (Casos)
 *     CV28 = VACUNACION NO OPORTUNA - PENTAVALENTE - 2DA DOSIS    (Casos)
 *     CW28 = VACUNACION NO OPORTUNA - PENTAVALENTE - 3RA DOSIS    (Casos)
 *
 *   VACUNACION NO OPORTUNA - SPR:
 *     DI28 = VACUNACION NO OPORTUNA - SPR - 1RA DOSIS      (Casos)
 *     DJ28 = VACUNACION NO OPORTUNA - SPR - 2DA DOSIS      (Casos)
 *
 *   REFUERZOS:
 *     TW28 = REFUERZO PENTAVALENTE - 1RA DOSIS             (Casos)
 *     DO28 = REFUERZO ANTIPOLIO IPV- 1RA DOSIS             (Casos)
 *
 *   Seccion D - fila 28 (Casos por vacuna/dosis, Seccion D):
 *
 *     DS28 = Neumococo con Comorbilidad                    (Casos)
 *     EE28 = Pentavalente No vacunado D1                   (Casos)
 *     EF28 = Pentavalente No vacunado D2                   (Casos)
 *     EG28 = Pentavalente No vacunado D3                   (Casos)
 *     EX28 = Refuerzo DPT                                   (Casos)
 *     EY28 = Refuerzo Antipolio IPV                         (Casos)
 *     DQ28 = INFLUENZA CON COMORBILIDAD - 1RA DOSIS        (Casos)
 *     DR28 = INFLUENZA SIN COMORBILIDAD - 1RA DOSIS        (Casos)
 *     DX28 = ANTIAMARILICA                                  (Casos)
 *     ES28 = SPR 1RA Dosis                                  (Casos)
 *     ET28 = SPR 2DA Dosis                                  (Casos)
 *     TX28 = REFUERZO PENTAVALENTE                          (Casos)
 *
 *   Seccion E1 - fila 28 (Casos por vacuna/dosis, DE 04 ANIOS):
 *
 *     FA28 = INFLUENZA CON COMORBILIDAD - 1RA DOSIS        (Casos)
 *     FB28 = INFLUENZA SIN COMORBILIDAD - 1RA DOSIS         (Casos)
 *     FH28 = ANTIAMARILICA                                  (Casos)
 *     GC28 = SPR 1RA Dosis                                  (Casos)
 *     GD28 = SPR 2DA Dosis                                  (Casos)
 *     GI28 = REFUERZO ANTIPOLIO(IPV)                        (Casos)
 *     GH28 = REFUERZO DPT                                   (Casos)
 *     GJ28 = REFUERZO ANTIPOLIO(APO)                         (Casos)
 *     FC28 = Neumococo con Comorbilidad                     (Casos)
 *     FO28 = Pentavalente D1 -No vacunado                   (Casos)
 *     FP28 = Pentavalente D2 -No vacunado                   (Casos)
 *     FQ28 = Pentavalente D3 -No vacunado                   (Casos)
 *     TY28 = Refuerzo Pentavalente                          (Casos)
 *
 *   Seccion E2 - fila 28 (Casos por vacuna/dosis, DE 05 - 07 ANIOS):
 *
 *     TD28 = REFUERZO DPT                                   (Casos)
 *     SK28 = Pentavalente D1 -No vacunado                   (Casos)
 *     SL28 = Pentavalente D2 -No vacunado                   (Casos)
 *     SM28 = Pentavalente D3 -No vacunado                   (Casos)
 *
 *   Seccion F - fila 28 (dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS,
 *   por grupo de edad y dosis):
 *
 *   GRUPO DE EDAD "05 y 09 años" (Mujeres 5 a 9 anos):
 *     GK28 = dT 1ra - Mujeres 5 a 9 anos          (D1) (Casos)
 *     GL28 = dT 2da - Mujeres 5 a 9 anos          (D2) (Casos)
 *     GM28 = dT 3ra - Mujeres 5 a 9 anos          (D3) (Casos)
 *
 *   GRUPO DE EDAD "10 y 11 años" (Mujeres 10 a 11 anos):
 *     GO28 = dT 1ra - Mujeres 10 a 11 anos        (D1) (Casos)
 *     GP28 = dT 2da - Mujeres 10 a 11 anos        (D2) (Casos)
 *     GQ28 = dT 3ra - Mujeres 10 a 11 anos        (D3) (Casos)
 *
 *   GRUPO DE EDAD "12 y 17 años" (Mujeres 12 a 17 anos):
 *     GT28 = dT 1ra - Mujeres 12 a 17 anos        (D1) (Casos)
 *     GU28 = dT 2da - Mujeres 12 a 17 anos        (D2) (Casos)
 *     GV28 = dT 3ra - Mujeres 12 a 17 anos        (D3) (Casos)
 *
 *   GRUPO DE EDAD "18 y 29 años" (Mujeres 18 a 29 anos):
 *     GY28 = dT 1ra - Mujeres 18 a 29 anos        (D1) (Casos)
 *     GZ28 = dT 2da - Mujeres 18 a 29 anos        (D2) (Casos)
 *     HA28 = dT 3ra - Mujeres 18 a 29 anos        (D3) (Casos)
 *
 *   GRUPO DE EDAD "30 y 49 años" (Mujeres 30 a 49 anos):
 *     HD28 = dT 1ra - Mujeres 30 a 49 anos        (D1) (Casos)
 *     HE28 = dT 2da - Mujeres 30 a 49 anos        (D2) (Casos)
 *     HF28 = dT 3ra - Mujeres 30 a 49 anos        (D3) (Casos)
 *
 *   GRUPO DE EDAD "50 y 59 años" (Mujeres 50 a 59 anos):
 *     HI28 = dT 1ra - Mujeres 50 a 59 anos        (D1) (Casos)
 *     HJ28 = dT 2da - Mujeres 50 a 59 anos        (D2) (Casos)
 *     HK28 = dT 3ra - Mujeres 50 a 59 anos        (D3) (Casos)
 *
 *   GRUPO DE EDAD "60 años a mas" (Mujeres 60 a mas anos):
 *     HN28 = dT 1ra - Mujeres 60 a mas anos       (D1) (Casos)
 *     HO28 = dT 2da - Mujeres 60 a mas anos       (D2) (Casos)
 *     HP28 = dT 3ra - Mujeres 60 a mas anos       (D3) (Casos)
 *
 *   Seccion F2 - fila 28 (dT EN GESTANTES POR GRUPO DE EDAD 10-59 ANIOS,
 *   por grupo de edad y dosis D1/D2/D3):
 *
 *   GRUPO DE EDAD "10 y 11 años" (Gestantes 10 a 11 anos):
 *     HS28 = dT 1ra -10_11A          (D1) (Casos)
 *     HT28 = dT 2da -10_11A          (D2) (Casos)
 *     HU28 = dT 3ra -10_11A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "12 y 17 años" (Gestantes 12 a 17 anos):
 *     HW28 = dT 1ra -12_17A          (D1) (Casos)
 *     HX28 = dT 2da -12_17A          (D2) (Casos)
 *     HY28 = dT 3ra -12_17A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "18 y 29 años" (Gestantes 18 a 29 anos):
 *     IB28 = dT 1ra -18_29A          (D1) (Casos)
 *     IC28 = dT 2da -18_29A          (D2) (Casos)
 *     ID28 = dT 3ra -18_29A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "30 y 49 años" (Gestantes 30 a 49 anos):
 *     IG28 = dT 1ra -30_49A          (D1) (Casos)
 *     IH28 = dT 2da -30_49A          (D2) (Casos)
 *     II28 = dT 3ra -30_49A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "50 y 59 años" (Gestantes 50 a 59 anos):
 *     IL28 = dT 1ra -50_59A          (D1) (Casos)
 *     IM28 = dT 2da -50_59A          (D2) (Casos)
 *     IN28 = dT 3ra -50_59A          (D3) (Casos)
 *
 *   Seccion G - fila 28 (dT ADULTO EN VARONES EN RIESGO POR GRUPO DE EDAD,
 *   por grupo de edad y dosis D1/D2/D3):
 *
 *   GRUPO DE EDAD "05 y 09 años" (Varones 5 a 9 anos):
 *     NV28 = dT 1ra -05_09A          (D1) (Casos)
 *     NW28 = dT 2da -05_09A          (D2) (Casos)
 *     NX28 = dT 3ra -05_09A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "10 y 11 años" (Varones 10 a 11 anos):
 *     NZ28 = dT 1ra -10_11A          (D1) (Casos)
 *     OA28 = dT 2da -10_11A          (D2) (Casos)
 *     OB28 = dT 3ra -10_11A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "12 y 17 años" (Varones 12 a 17 anos):
 *     IQ28 = dT 1ra -12_17A          (D1) (Casos)
 *     IR28 = dT 2da -12_17A          (D2) (Casos)
 *     IS28 = dT 3ra -12_17A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "18 y 29 años" (Varones 18 a 29 anos):
 *     IU28 = dT 1ra -18_29A          (D1) (Casos)
 *     IV28 = dT 2da -18_29A          (D2) (Casos)
 *     IW28 = dT 3ra -18_29A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "30 y 59 años" (Varones 30 a 59 anos):
 *     IY28 = dT 1ra -30_59A          (D1) (Casos)
 *     IZ28 = dT 2da -30_59A          (D2) (Casos)
 *     JA28 = dT 3ra -30_59A          (D3) (Casos)
 *
 *   GRUPO DE EDAD "60 años a mas" (Varones 60 a mas anos):
 *     JC28 = dT 1ra -60A_MAS         (D1) (Casos)
 *     JD28 = dT 2da -60A_MAS         (D2) (Casos)
 *     JE28 = dT 3ra -60A_MAS         (D3) (Casos)
 *
 *   Seccion H - fila 28 (INFLUENZA ESTACIONAL EN OTROS GRUPOS, por grupo
 *   de edad / riesgo):
 *
 *     JG28 = CON COMORBILIDAD 05_11A                        (Casos)
 *     JH28 = CON COMORBILIDAD 12_17A                        (Casos)
 *     JI28 = CON COMORBILIDAD 18_29A                        (Casos)
 *     JJ28 = CON COMORBILIDAD 30_49A                        (Casos)
 *     JK28 = CON COMORBILIDAD 50_59A                        (Casos)
 *     JL28 = SIN COMORBILIDAD 5_11A                         (Casos)
 *     JM28 = SIN COMORBILIDAD 12_17A                        (Casos)
 *     JN28 = SIN COMORBILIDAD 18_29A                        (Casos)
 *     JO28 = SIN COMORBILIDAD 30_49A                        (Casos)
 *     JP28 = SIN COMORBILIDAD 50_59A                        (Casos)
 *     JQ28 = MAYORES DE 60A                                 (Casos)
 *     JR28 = GESTANTES                                      (Casos)
 *     JS28 = PUERPERAS                                      (Casos)
 *     JT28 = PERSONAL DE SALUD                              (Casos)
 *     JZ28 = ESTUDIANTES                                    (Casos)
 *     KE28 = COMUNIDADES NATIVAS                            (Casos)
 *     KG28 = PERSONA CON DISCAPACIDAD                       (Casos)
 *     KH28 = OTROS                                          (Casos)
 *
 *   Seccion J - fila 28 (POBLACION DE 05 A 59 ANIOS: VACUNACION CONTRA
 *   LA HEPATITIS B, por grupo de edad y dosis D1/D2/D3). Layout
 *   "matriz_dosis": las 3 lineas (D1, D2, D3) de cada grupo de edad
 *   comparten la MISMA etiqueta en ESNI_LINEA_REPORTE y se distinguen
 *   unicamente por el campo dosis_codigo. Las celdas Tot. (KZ28, LD28,
 *   LH28, LL28, LT28) son formulas =SUM() propias de la plantilla.
 *
 *   GRUPO DE EDAD "05 y 11 años" (etiqueta en BD: "05 a 11 años"):
 *     KW28 = Hepatitis B - 05 a 11 años - D1 (Casos)
 *     KX28 = Hepatitis B - 05 a 11 años - D2 (Casos)
 *     KY28 = Hepatitis B - 05 a 11 años - D3 (Casos)
 *
 *   GRUPO DE EDAD "12 y 17 años" (etiqueta en BD: "12 a 17 años"):
 *     LA28 = Hepatitis B - 12 a 17 años - D1 (Casos)
 *     LB28 = Hepatitis B - 12 a 17 años - D2 (Casos)
 *     LC28 = Hepatitis B - 12 a 17 años - D3 (Casos)
 *
 *   GRUPO DE EDAD "18 y 29 años" (etiqueta en BD: "18 a 29 años"):
 *     LE28 = Hepatitis B - 18 a 29 años - D1 (Casos)
 *     LF28 = Hepatitis B - 18 a 29 años - D2 (Casos)
 *     LG28 = Hepatitis B - 18 a 29 años - D3 (Casos)
 *
 *   GRUPO DE EDAD "30 y 59 años" (etiqueta en BD: "30 a 59 años"):
 *     LI28 = Hepatitis B - 30 a 59 años - D1 (Casos)
 *     LJ28 = Hepatitis B - 30 a 59 años - D2 (Casos)
 *     LK28 = Hepatitis B - 30 a 59 años - D3 (Casos)
 *
 *   GRUPO "Personal de Salud":
 *     LQ28 = Hepatitis B - Personal de Salud - D1 (Casos)
 *     LR28 = Hepatitis B - Personal de Salud - D2 (Casos)
 *     LS28 = Hepatitis B - Personal de Salud - D3 (Casos)
 *
 *   GRUPO "Gestantes" (sub-zona J.1 HEPATITIS / PERSONAS DE RIESGO):
 *     TM28 = Hepatitis B - Gestantes - D1 (Casos)
 *     TN28 = Hepatitis B - Gestantes - D2 (Casos)
 *     TO28 = Hepatitis B - Gestantes - D3 (Casos)
 *
 *   Seccion K - fila 28 (ANTIAMARILICA EN POBLACION NO VACUNADA Y
 *   VIAJEROS A ZONAS ENDEMICAS, por grupo de edad / riesgo). Layout
 *   "total_uno" (al igual que la seccion H): cada grupo de edad tiene una
 *   unica linea en ESNI_LINEA_REPORTE cuyo valor es directamente el TOTAL
 *   de dosis aplicadas para ese grupo (no hay desglose por dosis). Las
 *   celdas MD28, MF28, MH28, MJ28 y ML28 son formulas =MC28, =ME28, =MG28,
 *   =MI28 y =MK28 propias de la plantilla.
 *
 *     MC28 = 05 a 11 años - Total                            (Casos)
 *     ME28 = 12 a 17 años - Total                            (Casos)
 *     MG28 = 18 a 29 años - Total                            (Casos)
 *     MI28 = 30 a 59 años - Total                            (Casos)
 *     MK28 = 60 + años - Total                               (Casos)
 *
 *   Seccion L - fila 28 (SOLO GESTANTES (dtpa), por grupo de edad /
 *   riesgo). Layout "total_uno" (al igual que las secciones H y K): cada
 *   grupo de edad tiene una unica linea en ESNI_LINEA_REPORTE cuyo valor
 *   es directamente el TOTAL de dosis aplicadas para ese grupo (no hay
 *   desglose por dosis).
 *
 *     MM28 = 12 a 17 años - Total                            (Casos)
 *     MN28 = 18 a 29 años - Total                            (Casos)
 *     MO28 = 30 a 49 años - Total                            (Casos)
 *
 *   Seccion N - fila 28 (VACUNA VPH: femenino y masculino, dosis unica,
 *   por grupo de edad). Layout "matriz_sexo": por cada grupo de edad
 *   existen DOS lineas en ESNI_LINEA_REPORTE con la MISMA etiqueta que se
 *   distinguen unicamente por el campo sexo ('M' = Masculino, 'F' =
 *   Femenino).
 *
 *     QF28 = 9 años   - Masculino                            (Casos)
 *     QG28 = 9 años   - Femenino                             (Casos)
 *     QM28 = 10 años  - Masculino                            (Casos)
 *     QN28 = 10 años  - Femenino                             (Casos)
 *     QT28 = 11 años  - Masculino                            (Casos)
 *     QU28 = 11 años  - Femenino                             (Casos)
 *     RA28 = 12 años  - Masculino                            (Casos)
 *     RB28 = 12 años  - Femenino                             (Casos)
 *     RH28 = 13 años  - Masculino                            (Casos)
 *     RI28 = 13 años  - Femenino                             (Casos)
 *     RO28 = 14 a mas - Masculino                            (Casos)
 *     RP28 = 14 a mas - Femenino                             (Casos)
 *
 *   Seccion O - fila 28 (NEUMOCOCO EN POBLACION EN RIESGO, por grupo de
 *   edad / riesgo). Layout "total_uno" (al igual que las secciones H, K y
 *   L): cada grupo de edad / riesgo tiene una unica linea en
 *   ESNI_LINEA_REPORTE cuyo valor es directamente el TOTAL de dosis
 *   aplicadas para ese grupo (no hay desglose por dosis).
 *
 *     OD28 = CON COMORBILIDAD 5-11a   - Total                 (Casos)
 *     OE28 = SIN COMORBILIDAD 50-59a  - Total                 (Casos)
 *     OF28 = 60 A MAS AÑOS            - Total                 (Casos)
 *     OI28 = PERSONAL DE SALUD        - Total                 (Casos)
 *     OJ28 = CON COMORBILIDAD 12-17a  - Total                 (Casos)
 *     OK28 = CON COMORBILIDAD 18-29a  - Total                 (Casos)
 *     OL28 = CON COMORBILIDAD 30-49a  - Total                 (Casos)
 *     OM28 = CON COMORBILIDAD 50-59a  - Total                 (Casos)
 *     ON28 = SIN COMORBILIDAD 05-11a  - Total                 (Casos)
 *     OO28 = SIN COMORBILIDAD 12-17a  - Total                 (Casos)
 *     OP28 = SIN COMORBILIDAD 18-29a  - Total                 (Casos)
 *     OQ28 = SIN COMORBILIDAD 30-49a  - Total                 (Casos)
 *
 *     Las celdas OG28 y OH28 no forman parte del mapeo (la plantilla las
 *     reserva para otros fines) y OR28 = SUM(OD28:OQ28) es formula propia
 *     de la plantilla (total de la Seccion O).
 *
 *   Seccion P - fila 28 (DT-DOSIS ADICIONALES, por grupo de edad). Layout
 *   "total_uno" (al igual que las secciones H, K, L y O): cada grupo de
 *   edad tiene una unica linea en ESNI_LINEA_REPORTE cuyo valor es
 *   directamente el TOTAL de dosis aplicadas para ese grupo (no hay
 *   desglose por dosis).
 *
 *     OS28 = 0 A 11 años   - Total                     (Casos)
 *     OT28 = 12 a 17 años  - Total                     (Casos)
 *     OU28 = 18 a 29 años  - Total                     (Casos)
 *     OV28 = 30 a 59 años  - Total                     (Casos)
 *     OW28 = 60 a mas años - Total                     (Casos)
 *
 *     OX28 = SUM(OS28:OW28) es formula propia de la plantilla (total de
 *     la Seccion P).
 *
 *   Seccion Q - fila 28 (VARICELA, por grupo de edad / riesgo). Layout
 *   "total_uno" (al igual que las secciones H, K, L, O y P): cada grupo
 *   de edad / riesgo tiene una unica linea en ESNI_LINEA_REPORTE cuyo
 *   valor es directamente el TOTAL de dosis aplicadas para ese grupo (no
 *   hay desglose por dosis; la varicela es dosis unica).
 *
 *     PG28 = 3 años            - Total                 (Casos)
 *     PK28 = 4 años            - Total                 (Casos)
 *     PO28 = 5 años            - Total                 (Casos)
 *     PS28 = 6 años a mas      - Total                 (Casos)
 *     PW28 = Personal de Salud - Total                 (Casos)
 *
 *     Las celdas siguen el layout horizontal de la fila 28 de la
 *     plantilla Operacional.xlsx (paso de 4 columnas entre cada celda
 *     de datos: PG, PK, PO, PS, PW).
 *
 *   Seccion R - fila 28 (HEPATITIS A, por grupo de edad / riesgo). Layout
 *   "total_uno" (al igual que las secciones H, K, L, O, P y Q): cada grupo
 *   de edad / riesgo tiene una unica linea en ESNI_LINEA_REPORTE cuyo
 *   valor es directamente el TOTAL de dosis aplicadas para ese grupo (no
 *   hay desglose por dosis; la hepatitis A es dosis unica).
 *
 *     TP28 = 2 AÑOS           - Total                 (Casos)
 *     TQ28 = 3 AÑOS           - Total                 (Casos)
 *     TR28 = 4 AÑOS           - Total                 (Casos)
 *
 *     Las celdas siguen el layout horizontal de la fila 28 de la
 *     plantilla Operacional.xlsx (columnas consecutivas TP, TQ, TR).
 *
 *   Seccion T - fila 28 (SPR-SARAMPION, por grupo de edad / riesgo).
 *   Layout "total_uno" (al igual que las secciones H, K, L, O, P, Q y R):
 *   cada grupo de edad / riesgo tiene una unica linea en
 *   ESNI_LINEA_REPORTE cuyo valor es directamente el TOTAL de dosis
 *   aplicadas para ese grupo (no hay desglose por dosis; la SPR es dosis
 *   unica).
 *
 *     UA28 = 5 a 10 años      - Total                 (Casos)
 *     UB28 = 11 a 59 años     - Total                 (Casos)
 *     UC28 = Trabajador de Salud - Total              (Casos)
 *
 *     Las celdas siguen el layout horizontal de la fila 28 de la
 *     plantilla Operacional.xlsx (columnas consecutivas UA, UB, UC).
 *
 * Funcionamiento:
 *   1) Recibe por GET los filtros: anio, mes, establecimiento (los mismos
 *      que reporte_esni.php).
 *   2) Ejecuta el motor data-driven de ESNI contra la tabla consolidada MySQL
 *      con Id_Ups = 301204 (estrategia Inmunizaciones).
 *   3) Indexa las lineas de la Seccion A, Seccion B, Seccion C, Seccion D,
 *      Seccion E1, Seccion E2, Seccion F, Seccion F2, Seccion G, Seccion H,
 *      Seccion K, Seccion L, Seccion O, Seccion P, Seccion Q, Seccion R y
 *      Seccion T por etiqueta normalizada; las de la
 *      Seccion J
 *      por la clave compuesta etiqueta normalizada + dosis_codigo (D1/D2/D3);
 *      y las de la Seccion N por la clave compuesta etiqueta normalizada +
 *      sexo (M/F).
 *   4) Recupera el conteo de cada vacuna/dosis con esniGetCasos().
 *   5) Llena la plantilla Operacional.xlsx con ExcelTemplateFiller (sin
 *      requerir PhpSpreadsheet ni composer, solo ZipArchive de PHP).
 *   6) Envia el archivo como descarga al navegador.
 */

require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';
require_once 'includes/ExcelTemplateFiller.php';

// ============================================================================
// 0. Validar que exista la plantilla en uploads/Operacional.xlsx
// ============================================================================
$templatePath = __DIR__ . '/uploads/Operacional.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    htmlErrorPlano(
        'Falta la plantilla Excel',
        'No se encontro <code>uploads/Operacional.xlsx</code> en el servidor.<br><br>' .
        '<b>Soluci&oacute;n:</b> Suba el archivo <code>Operacional.xlsx</code> a la carpeta <code>uploads/</code> del proyecto mediante FTP o el administrador de archivos del hosting.<br><br>' .
        'Ruta esperada: <code>' . htmlspecialchars($templatePath) . '</code>'
    );
}

// Verificar que la extension ZipArchive este disponible
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    htmlErrorPlano(
        'Extension ZIP no disponible',
        'El servidor PHP no tiene cargada la extension <code>zip</code> (clase <code>ZipArchive</code>).<br><br>' .
        '<b>Soluci&oacute;n:</b> En InfinityFree esto se activa desde el panel de control &rarr; PHP Configuration &rarr; marcar "zip". En otros hostings, editar <code>php.ini</code> y agregar <code>extension=zip</code>.'
    );
}

// Crear el directorio uploads/tmp/ con permisos adecuados (si no existe)
$uploadsTmp = __DIR__ . '/uploads/tmp';
if (!is_dir($uploadsTmp)) {
    @mkdir($uploadsTmp, 0755, true);
}

// ============================================================================
// 1. Filtros (los mismos que reporte_esni.php)
// ============================================================================
$pdo = getDBConnection();

if (!esniEsquemaInstalado($pdo)) {
    http_response_code(500);
    htmlErrorPlano(
        'Esquema ESNI no instalado',
        'El esquema de tablas ESNI no esta instalado en la base de datos.<br><br>' .
        '<b>Soluci&oacute;n:</b> Ejecute <code>Database/install_esni.sql</code> desde el administrador de MySQL o phpMyAdmin.'
    );
}

// Estrategia fija del modulo ESNI: solo Id_Ups = 301204 (Inmunizaciones)
define('ESNI_ID_UPS', '301204');

$filtros = [
    'anio'            => trim($_GET['anio'] ?? ''),
    'mes'             => trim($_GET['mes'] ?? ''),
    'establecimiento' => trim($_GET['establecimiento'] ?? ''),
    'id_ups'          => ESNI_ID_UPS,
];

// El valor de "establecimiento" llega como Codigo_Unico (cargado desde ZSPERENE).
// Se resuelve el nombre para mostrarlo en el encabezado de la plantilla.
$nombreEstablecimiento = '';
$estExport = esniGetEstablecimientosZS($pdo);
$establecimientosPermitidos = array_keys($estExport);
if ($filtros['establecimiento'] !== '') {
    $nombreEstablecimiento = $estExport[$filtros['establecimiento']] ?? $filtros['establecimiento'];
}

// ----------------------------------------------------------------------------
// Resolucion de NOMBRES legibles para las celdas de encabezado B5 (mes) y
// C28 (establecimiento). Se usa el nombre legible ("Enero", "P.S. Chazuta")
// en lugar del valor crudo del select (numero 1-12, Codigo_Unico), porque un
// Excel de reporte debe ser legible por humanos. Si prefiere el valor crudo,
// reemplazar $nombreMes por $filtros['mes'] y $nombreEst por
// $filtros['establecimiento'] en el array $cellValues (seccion 4).
// ----------------------------------------------------------------------------
$nombreMes = $filtros['mes'] !== ''
    ? getNombreMes((int)$filtros['mes'])
    : 'TODOS';
$nombreEst = $nombreEstablecimiento !== '' ? $nombreEstablecimiento : 'TODOS';

// ============================================================================
// 2. Ejecutar reporte ESNI
// ============================================================================
$cols = esniResolverColumnas($pdo);
$reporte = esniEjecutarReporte($pdo, $filtros, $cols, $establecimientosPermitidos);

if (!empty($reporte['error'])) {
    http_response_code(500);
    htmlErrorPlano(
        'Error al generar el reporte',
        'No se pudo generar el reporte ESNI.<br><br>' .
        '<b>Error tecnico:</b><br>' .
        '<pre style="background:#f8f9fa;padding:.6rem;border-radius:.25rem;overflow:auto;">' .
        htmlspecialchars($reporte['error']) . '</pre>'
    );
}

// ============================================================================
// 3. Indexar lineas de las SECCIONES A, B, C, D, E1, E2, F, F2, G, H, J, K, L, N, O, P, Q, R y T
// ----------------------------------------------------------------------------
// El motor de reglas devuelve $reporte['secciones'] con todas las secciones
// (A, B, C, D, E1, E2, F, F2, G, H, J, K, L, ...). Aqui nos interesan la
// seccion "A" (Menores de 01 anio), la seccion "B" (De 01 anio), la seccion
// "C" (Mayores de 01 anio), la seccion "D" (De 03 anios), la seccion "E1"
// (De 04 anios), la seccion "E2" (De 05 - 07 anios), la seccion "F"
// (dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS), la seccion "F2"
// (dT EN GESTANTES POR GRUPO DE EDAD 10-59 ANIOS), la seccion "G"
// (dT ADULTO EN VARONES EN RIESGO POR GRUPO DE EDAD), la seccion "H"
// (INFLUENZA ESTACIONAL EN OTROS GRUPOS), la seccion "K" (ANTIAMARILICA
// EN POBLACION NO VACUNADA Y VIAJEROS A ZONAS ENDEMICAS) y la seccion "L"
// (SOLO GESTANTES (dtpa)). Las etiquetas de las lineas pueden tener
// ligeras variaciones (espacios extra, Mayusculas) respecto a la
// nomenclatura de la plantilla. Por eso se normalizan con
// esniNormalizarEtiquetaPlano() que:
//   - Pasa a MAYUSCULAS
//   - Colapsa espacios multiples
//   - Quita espacios al inicio/final
//   - Quita asterisco inicial "*" (marcador de "linea informativa")
// ============================================================================
$casosPorEtiquetaA  = esniIndexarCasosSeccionPlano($reporte, 'A');
$casosPorEtiquetaB  = esniIndexarCasosSeccionPlano($reporte, 'B');
$casosPorEtiquetaC  = esniIndexarCasosSeccionPlano($reporte, 'C');
$casosPorEtiquetaD  = esniIndexarCasosSeccionPlano($reporte, 'D');
$casosPorEtiquetaE1 = esniIndexarCasosSeccionPlano($reporte, 'E1');
$casosPorEtiquetaE2 = esniIndexarCasosSeccionPlano($reporte, 'E2');
$casosPorEtiquetaF  = esniIndexarCasosSeccionPlano($reporte, 'F');
$casosPorEtiquetaF2 = esniIndexarCasosSeccionPlano($reporte, 'F2');
$casosPorEtiquetaG  = esniIndexarCasosSeccionPlano($reporte, 'G');
$casosPorEtiquetaH  = esniIndexarCasosSeccionPlano($reporte, 'H');

// ----------------------------------------------------------------------------
// La Seccion K (ANTIAMARILICA EN POBLACION NO VACUNADA Y VIAJEROS A ZONAS
// ENDEMICAS) tiene layout "total_uno" (al igual que la seccion H): cada grupo
// de edad tiene una unica linea cuyo valor es directamente el total de dosis
// aplicadas (columna "Total" del reporte ESNI). Por eso se indexa por etiqueta
// normalizada (mismo patron que A/B/C/H) y se recupera con esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaK  = esniIndexarCasosSeccionPlano($reporte, 'K');

// ----------------------------------------------------------------------------
// La Seccion L (SOLO GESTANTES (dtpa), id_seccion = 18) tiene layout
// "total_uno" (al igual que las secciones H y K): cada grupo de edad tiene
// una unica linea cuyo valor es directamente el total de dosis aplicadas
// (columna "Total" del reporte ESNI). Por eso se indexa por etiqueta
// normalizada (mismo patron que A/B/C/H/K) y se recupera con
// esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaL  = esniIndexarCasosSeccionPlano($reporte, 'L');

// ----------------------------------------------------------------------------
// La Seccion O (NEUMOCOCO EN POBLACION EN RIESGO, id_seccion = 13) tiene
// layout "total_uno" (al igual que las secciones H, K y L): cada grupo de
// edad / riesgo tiene una unica linea cuyo valor es directamente el total de
// dosis aplicadas (columna "Total" del reporte ESNI). Por eso se indexa por
// etiqueta normalizada (mismo patron que A/B/C/H/K/L) y se recupera con
// esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaO  = esniIndexarCasosSeccionPlano($reporte, 'O');

// ----------------------------------------------------------------------------
// La Seccion P (DT-DOSIS ADICIONALES, id_seccion = 21) tiene layout
// "total_uno" (al igual que las secciones H, K, L y O): cada grupo de edad
// tiene una unica linea cuyo valor es directamente el total de dosis
// aplicadas (columna "Total" del reporte ESNI). Por eso se indexa por
// etiqueta normalizada (mismo patron que A/B/C/H/K/L/O) y se recupera con
// esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaP  = esniIndexarCasosSeccionPlano($reporte, 'P');

// ----------------------------------------------------------------------------
// La Seccion Q (VARICELA) tiene layout "total_uno" (al igual que las
// secciones H, K, L, O y P): cada grupo de edad / riesgo tiene una unica
// linea cuyo valor es directamente el total de dosis aplicadas (columna
// "Total" del reporte ESNI). Por eso se indexa por etiqueta normalizada
// (mismo patron que A/B/C/H/K/L/O/P) y se recupera con esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaQ  = esniIndexarCasosSeccionPlano($reporte, 'Q');

// ----------------------------------------------------------------------------
// La Seccion R (HEPATITIS A, id_seccion = 19) tiene layout "total_uno" (al
// igual que las secciones H, K, L, O, P y Q): cada grupo de edad / riesgo
// tiene una unica linea cuyo valor es directamente el total de dosis
// aplicadas (columna "Total" del reporte ESNI). Por eso se indexa por
// etiqueta normalizada (mismo patron que A/B/C/H/K/L/O/P/Q) y se recupera
// con esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaR  = esniIndexarCasosSeccionPlano($reporte, 'R');

// ----------------------------------------------------------------------------
// La Seccion T (SPR-SARAMPION, id_seccion = 20) tiene layout "total_uno"
// (al igual que las secciones H, K, L, O, P, Q y R): cada grupo de edad /
// riesgo tiene una unica linea cuyo valor es directamente el total de
// dosis aplicadas (columna "Total" del reporte ESNI). Por eso se indexa
// por etiqueta normalizada (mismo patron que A/B/C/H/K/L/O/P/Q/R) y se
// recupera con esniGetCasosPlano().
// ----------------------------------------------------------------------------
$casosPorEtiquetaT  = esniIndexarCasosSeccionPlano($reporte, 'T');

// ----------------------------------------------------------------------------
// La Seccion N (VACUNA VPH, id_seccion = 14) tiene layout "matriz_sexo": por
// cada grupo de edad existen DOS lineas en ESNI_LINEA_REPORTE con la MISMA
// etiqueta ("9 años", "10 años", ...) que se distinguen unicamente por el
// campo sexo ('M' = Masculino, 'F' = Femenino). Por eso NO se usa
// esniIndexarCasosSeccionPlano('N') (que sumaria las cantidades de las
// etiquetas repetidas, mezclando Masculino + Femenino); el indexado se hace
// con una clave compuesta "etiqueta_normalizada|sexo" (mismo criterio que la
// Seccion J) para recuperar el conteo de cada sexo por separado.
// ----------------------------------------------------------------------------
$casosPorEtiquetaSexoN = []; // [etqNorm . '|' . sexo => int]
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'N') !== 0) {
        continue;
    }
    foreach ($sec['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiquetaPlano($lin['etiqueta']);
        $sexoCod = isset($lin['sexo']) ? strtoupper(trim((string)$lin['sexo'])) : '';
        $key = $etqNorm . '|' . $sexoCod;
        // Si la clave ya existe (no deberia), sumamos las cantidades para
        // ser tolerantes con configuraciones que dupliquen la misma linea.
        if (isset($casosPorEtiquetaSexoN[$key])) {
            $casosPorEtiquetaSexoN[$key] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaSexoN[$key] = (int)$lin['cantidad'];
        }
    }
    break; // Solo existe una seccion con ese codigo
}

// ----------------------------------------------------------------------------
// La Seccion J (POBLACION DE 05 A 59 ANIOS: VACUNACION CONTRA LA HEPATITIS B)
// tiene layout "matriz_dosis" y, a diferencia de las secciones F/F2/G (donde
// cada linea tiene una etiqueta unica por dosis, p.ej. "dT 1ra - Mujeres 5 a 9
// anos"), en la seccion J las 3 lineas (D1, D2, D3) de un mismo grupo de edad
// comparten la MISMA etiqueta (p.ej. "05 a 11 años", "Personal de Salud",
// "Gestantes") y se distinguen unicamente por el campo dosis_codigo. Por eso
// NO se usa esniIndexarCasosSeccionPlano('J') (que sumaria las cantidades de
// las etiquetas repetidas, mezclando D1+D2+D3); el indexado se hace con una
// clave compuesta "etiqueta_normalizada|dosis_codigo" (mismo criterio que
// esni_export.php) para recuperar el conteo de cada dosis por separado.
// ----------------------------------------------------------------------------
$casosPorEtiquetaDosisJ = []; // [etqNorm . '|' . dosis_codigo => int]
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'J') !== 0) {
        continue;
    }
    foreach ($sec['lineas'] as $lin) {
        $etqNorm  = esniNormalizarEtiquetaPlano($lin['etiqueta']);
        $dosisCod = isset($lin['dosis_codigo']) ? strtoupper(trim((string)$lin['dosis_codigo'])) : '';
        $key = $etqNorm . '|' . $dosisCod;
        // Si la clave ya existe (no deberia), sumamos las cantidades para
        // ser tolerantes con configuraciones que dupliquen la misma linea.
        if (isset($casosPorEtiquetaDosisJ[$key])) {
            $casosPorEtiquetaDosisJ[$key] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaDosisJ[$key] = (int)$lin['cantidad'];
        }
    }
    break; // Solo existe una seccion con ese codigo
}

// ============================================================================
// 4. Recuperar casos por linea y mapear a celdas de la plantilla
// ----------------------------------------------------------------------------
// Los mapas $cellMapA, $cellMapB, $cellMapC, $cellMapD, $cellMapE1, $cellMapE2,
// $cellMapF, $cellMapF2, $cellMapG, $cellMapH, $cellMapJ, $cellMapK, $cellMapL
// y $cellMapN asocian cada celda
// destino de la plantilla Operacional.xlsx (fila 28) con la etiqueta exacta de
// la linea (campo ESNI_LINEA_REPORTE.etiqueta) de donde se toma el valor
// "Casos". $cellMapA toma los casos de la Seccion A (Menores de 01 anio),
// $cellMapB los de la Seccion B (De 01 anio), $cellMapC los de la Seccion C
// (Mayores de 01 anio), $cellMapD los de la Seccion D (De 03 anios),
// $cellMapE1 los de la Seccion E1 (De 04 anios), $cellMapE2 los de la Seccion
// E2 (De 05 - 07 anios), $cellMapF los de la Seccion F (dT ADULTO EN MUJERES
// EN EDAD FERTIL DESDE 5 ANIOS, por grupo de edad y dosis D1/D2/D3),
// $cellMapF2 los de la Seccion F2 (dT EN GESTANTES POR GRUPO DE EDAD 10-59
// ANIOS, por grupo de edad y dosis D1/D2/D3), $cellMapG los de la Seccion G
// (dT ADULTO EN VARONES EN RIESGO POR GRUPO DE EDAD, por grupo de edad y
// dosis D1/D2/D3), $cellMapH los de la Seccion H (INFLUENZA ESTACIONAL EN
// OTROS GRUPOS, por grupo de edad / riesgo) y $cellMapJ los de la Seccion J
// (HEPATITIS B EN POBLACION DE 05 A 59 ANIOS, por grupo de edad y dosis
// D1/D2/D3; cada celda se mapea al par [etiqueta, dosis_codigo] y se
// resuelve con esniGetCasosJDosisPlano()), $cellMapL los de la Seccion L
// (SOLO GESTANTES (dtpa), por grupo de edad / riesgo), $cellMapO los de la
// Seccion O (NEUMOCOCO EN POBLACION EN RIESGO, por grupo de edad / riesgo),
// $cellMapP los de la Seccion P (DT-DOSIS ADICIONALES, por grupo de edad),
// $cellMapQ los de la Seccion Q (VARICELA, por grupo de edad / riesgo),
// $cellMapR los de la Seccion R (HEPATITIS A, por grupo de edad / riesgo),
// $cellMapT los de la Seccion T (SPR-SARAMPION, por grupo de edad / riesgo)
// y $cellMapN los de la
// Seccion N (VACUNA VPH, por grupo de edad y sexo M/F; cada celda se mapea
// al par [etiqueta, sexo] y se resuelve con esniGetCasosNSexoPlano()).
// ============================================================================
$cellMapA = [
    // BCG
    'E28' => 'BCG - 24 HORAS',
    'F28' => 'BCG - 28 DIAS',
    'G28' => 'BCG - DE 01M A 11M 29D',
    // HEPATITIS VIRAL B
    'I28' => 'HEPATITIS VIRAL B - 12 HORAS',
    'J28' => 'HEPATITIS VIRAL B - 24 HORAS',
    // ANTIPOLIO - IPV
    'QA28' => 'ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS',
    'QB28' => 'ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS',
    'QC28' => 'ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS',
    // PENTAVALENTE
    'Q28' => 'PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS',
    'R28' => 'PENTAVALENTE - 02, 04 Y 06 MESES - 2RA DOSIS',
    'S28' => 'PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS',
    // ROTAVIRUS
    'AF28' => 'ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS',
    'AG28' => 'ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS',
    // NEUMOCOCO
    'AJ28' => 'NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS',
    'AK28' => 'NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS',
    // INFLUENZA
    'AN28' => 'INFLUENZA - 06 Y 07 MESES - 1RA DOSIS',
    'AO28' => 'INFLUENZA - 06 Y 07 MESES - 2DA DOSIS',
];

$cellMapB = [
    // NEUMOCOCO (01 anio)
    'AX28' => '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS',
    // SPR (01 anio)
    'AY28' => '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS',
    // VARICELA
    'BA28' => 'VARICELA 1RA',
    // INFLUENZA (dosis unica)
    'AZ28' => '1A 11M 29D - DOSIS UNICA - INFLUENZA',
    // NEUMOCOCO 12 a 23 meses
    'BB28' => 'NEUMOCOCO 1RA',
    'BC28' => 'NEUMOCOCO 2DA',
    // ANTIAMARILICA (15 meses)
    'BF28' => '15 MESES - ANTIAMARILICA - DOSIS UNICA',
    // HEPATITIS A (15 meses)
    'TG28' => '15 MESES - HEPATITIS A - DOSIS UNICA',
    // SPR 2da dosis (18 meses)
    'BG28' => '18 MESES - SPR - 2DA DOSIS',
    // Refuerzo DPT (18 meses)
    'BH28' => '18 MESES - REF. DPT - 1RA DOSIS',
    // Refuerzo IPV (18 meses)
    'BI28' => '18 MESES - REF. IPV',
    // Refuerzo PENTAVALENTE (18 meses)
    'TV28' => '18 MESES - REF. PENTAVALENTE',
    // Vacunacion no oportuna
    'TH28' => 'No vacunado IPV',
    'BQ28' => 'No vacunado PENTAVALENTE 2da',
    'BR28' => 'No vacunado PENTAVALENTE 3ra',
];

$cellMapC = [
    // INFLUENZA / NEUMOCOCO CON/SIN COMORBILIDAD
    'CF28' => 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS',
    'CG28' => 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS',
    'CH28' => 'NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS',
    // VACUNACION NO OPORTUNA - NEUMOCOCO
    'CI28' => 'VACUNACION NO OPORTUNA - NEUMOCOCO D1',
    'CJ28' => 'VACUNACION NO OPORTUNA - NEUMOCOCO D2',
    'CK28' => 'VACUNACION NO OPORTUNA - NEUMOCOCO D3',
    // ANTIAMARILICA
    'CN28' => 'ANTIAMARILICA - 1RA DOSIS',
    // VACUNACION NO OPORTUNA - ANTIPOLIO - IPV
    'CO28' => 'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 1RA DOSIS',
    'CP28' => 'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 2DA DOSIS',
    'TI28' => 'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 3RA DOSIS',
    // VACUNACION NO OPORTUNA - PENTAVALENTE
    'CU28' => 'VACUNACION NO OPORTUNA - PENTAVALENTE - 1RA DOSIS',
    'CV28' => 'VACUNACION NO OPORTUNA - PENTAVALENTE - 2DA DOSIS',
    'CW28' => 'VACUNACION NO OPORTUNA - PENTAVALENTE - 3RA DOSIS',
    // VACUNACION NO OPORTUNA - SPR
    'DI28' => 'VACUNACION NO OPORTUNA - SPR - 1RA DOSIS',
    'DJ28' => 'VACUNACION NO OPORTUNA - SPR - 2DA DOSIS',
    // REFUERZOS
    'TW28' => 'REFUERZO PENTAVALENTE - 1RA DOSIS',
    'DO28' => 'REFUERZO ANTIPOLIO IPV- 1RA DOSIS',
];

$cellMapD = [
    // NEUMOCOCO CON COMORBILIDAD
    'DS28' => 'Neumococo con Comorbilidad',
    // PENTAVALENTE NO VACUNADO
    'EE28' => 'Pentavalente No vacunado D1',
    'EF28' => 'Pentavalente No vacunado D2',
    'EG28' => 'Pentavalente No vacunado D3',
    // REFUERZOS
    'EX28' => 'Refuerzo DPT',
    'EY28' => 'Refuerzo Antipolio IPV',
    // INFLUENZA CON/SIN COMORBILIDAD
    'DQ28' => 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS',
    'DR28' => 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS',
    // ANTIAMARILICA
    'DX28' => 'ANTIAMARILICA',
    // SPR
    'ES28' => 'SPR 1RA Dosis',
    'ET28' => 'SPR 2DA Dosis',
    // REFUERZO PENTAVALENTE
    'TX28' => 'REFUERZO PENTAVALENTE',
];

$cellMapE1 = [
    // INFLUENZA CON/SIN COMORBILIDAD
    'FA28' => 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS',
    'FB28' => 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS',
    // ANTIAMARILICA
    'FH28' => 'ANTIAMARILICA',
    // SPR
    'GC28' => 'SPR 1RA Dosis',
    'GD28' => 'SPR 2DA Dosis',
    // REFUERZOS ANTIPOLIO
    'GI28' => 'REFUERZO ANTIPOLIO(IPV)',
    'GH28' => 'REFUERZO DPT',
    'GJ28' => 'REFUERZO ANTIPOLIO(APO)',
    // NEUMOCOCO CON COMORBILIDAD
    'FC28' => 'Neumococo con Comorbilidad',
    // PENTAVALENTE NO VACUNADO
    'FO28' => 'Pentavalente D1 -No vacunado',
    'FP28' => 'Pentavalente D2 -No vacunado',
    'FQ28' => 'Pentavalente D3 -No vacunado',
    // REFUERZO PENTAVALENTE
    'TY28' => 'Refuerzo Pentavalente',
];

$cellMapE2 = [
    // REFUERZO DPT
    'TD28' => 'REFUERZO DPT',
    // PENTAVALENTE NO VACUNADO
    'SK28' => 'Pentavalente D1 -No vacunado',
    'SL28' => 'Pentavalente D2 -No vacunado',
    'SM28' => 'Pentavalente D3 -No vacunado',
];

$cellMapF = [
    // GRUPO DE EDAD "05 y 09 años" (Mujeres 5 a 9 anos)
    // D1 / D2 / D3
    'GK28' => 'dT 1ra - Mujeres 5 a 9 anos',
    'GL28' => 'dT 2da - Mujeres 5 a 9 anos',
    'GM28' => 'dT 3ra - Mujeres 5 a 9 anos',
    // GRUPO DE EDAD "10 y 11 años" (Mujeres 10 a 11 anos)
    // D1 / D2 / D3
    'GO28' => 'dT 1ra - Mujeres 10 a 11 anos',
    'GP28' => 'dT 2da - Mujeres 10 a 11 anos',
    'GQ28' => 'dT 3ra - Mujeres 10 a 11 anos',
    // GRUPO DE EDAD "12 y 17 años" (Mujeres 12 a 17 anos)
    // D1 / D2 / D3
    'GT28' => 'dT 1ra - Mujeres 12 a 17 anos',
    'GU28' => 'dT 2da - Mujeres 12 a 17 anos',
    'GV28' => 'dT 3ra - Mujeres 12 a 17 anos',
    // GRUPO DE EDAD "18 y 29 años" (Mujeres 18 a 29 anos)
    // D1 / D2 / D3
    'GY28' => 'dT 1ra - Mujeres 18 a 29 anos',
    'GZ28' => 'dT 2da - Mujeres 18 a 29 anos',
    'HA28' => 'dT 3ra - Mujeres 18 a 29 anos',
    // GRUPO DE EDAD "30 y 49 años" (Mujeres 30 a 49 anos)
    // D1 / D2 / D3
    'HD28' => 'dT 1ra - Mujeres 30 a 49 anos',
    'HE28' => 'dT 2da - Mujeres 30 a 49 anos',
    'HF28' => 'dT 3ra - Mujeres 30 a 49 anos',
    // GRUPO DE EDAD "50 y 59 años" (Mujeres 50 a 59 anos)
    // D1 / D2 / D3
    'HI28' => 'dT 1ra - Mujeres 50 a 59 anos',
    'HJ28' => 'dT 2da - Mujeres 50 a 59 anos',
    'HK28' => 'dT 3ra - Mujeres 50 a 59 anos',
    // GRUPO DE EDAD "60 años a mas" (Mujeres 60 a mas anos)
    // D1 / D2 / D3
    'HN28' => 'dT 1ra - Mujeres 60 a mas anos',
    'HO28' => 'dT 2da - Mujeres 60 a mas anos',
    'HP28' => 'dT 3ra - Mujeres 60 a mas anos',
];

$cellMapF2 = [
    // GRUPO DE EDAD "10 y 11 años" (Gestantes 10 a 11 anos)
    // D1 / D2 / D3
    'HS28' => 'dT 1ra -10_11A',
    'HT28' => 'dT 2da -10_11A',
    'HU28' => 'dT 3ra -10_11A',
    // GRUPO DE EDAD "12 y 17 años" (Gestantes 12 a 17 anos)
    // D1 / D2 / D3
    'HW28' => 'dT 1ra -12_17A',
    'HX28' => 'dT 2da -12_17A',
    'HY28' => 'dT 3ra -12_17A',
    // GRUPO DE EDAD "18 y 29 años" (Gestantes 18 a 29 anos)
    // D1 / D2 / D3
    'IB28' => 'dT 1ra -18_29A',
    'IC28' => 'dT 2da -18_29A',
    'ID28' => 'dT 3ra -18_29A',
    // GRUPO DE EDAD "30 y 49 años" (Gestantes 30 a 49 anos)
    // D1 / D2 / D3
    'IG28' => 'dT 1ra -30_49A',
    'IH28' => 'dT 2da -30_49A',
    'II28' => 'dT 3ra -30_49A',
    // GRUPO DE EDAD "50 y 59 años" (Gestantes 50 a 59 anos)
    // D1 / D2 / D3
    'IL28' => 'dT 1ra -50_59A',
    'IM28' => 'dT 2da -50_59A',
    'IN28' => 'dT 3ra -50_59A',
];

$cellMapG = [
    // GRUPO DE EDAD "05 y 09 años" (Varones 5 a 9 anos)
    // D1 / D2 / D3
    'NV28' => 'dT 1ra -05_09A',
    'NW28' => 'dT 2da -05_09A',
    'NX28' => 'dT 3ra -05_09A',
    // GRUPO DE EDAD "10 y 11 años" (Varones 10 a 11 anos)
    // D1 / D2 / D3
    'NZ28' => 'dT 1ra -10_11A',
    'OA28' => 'dT 2da -10_11A',
    'OB28' => 'dT 3ra -10_11A',
    // GRUPO DE EDAD "12 y 17 años" (Varones 12 a 17 anos)
    // D1 / D2 / D3
    'IQ28' => 'dT 1ra -12_17A',
    'IR28' => 'dT 2da -12_17A',
    'IS28' => 'dT 3ra -12_17A',
    // GRUPO DE EDAD "18 y 29 años" (Varones 18 a 29 anos)
    // D1 / D2 / D3
    'IU28' => 'dT 1ra -18_29A',
    'IV28' => 'dT 2da -18_29A',
    'IW28' => 'dT 3ra -18_29A',
    // GRUPO DE EDAD "30 y 59 años" (Varones 30 a 59 anos)
    // D1 / D2 / D3
    'IY28' => 'dT 1ra -30_59A',
    'IZ28' => 'dT 2da -30_59A',
    'JA28' => 'dT 3ra -30_59A',
    // GRUPO DE EDAD "60 años a mas" (Varones 60 a mas anos)
    // D1 / D2 / D3
    'JC28' => 'dT 1ra -60A_MAS',
    'JD28' => 'dT 2da -60A_MAS',
    'JE28' => 'dT 3ra -60A_MAS',
];

$cellMapH = [
    // CON COMORBILIDAD
    'JG28' => 'CON COMORBILIDAD 05_11A',
    'JH28' => 'CON COMORBILIDAD 12_17A',
    'JI28' => 'CON COMORBILIDAD 18_29A',
    'JJ28' => 'CON COMORBILIDAD 30_49A',
    'JK28' => 'CON COMORBILIDAD 50_59A',
    // SIN COMORBILIDAD
    'JL28' => 'SIN COMORBILIDAD 5_11A',
    'JM28' => 'SIN COMORBILIDAD 12_17A',
    'JN28' => 'SIN COMORBILIDAD 18_29A',
    'JO28' => 'SIN COMORBILIDAD 30_49A',
    'JP28' => 'SIN COMORBILIDAD 50_59A',
    // OTROS GRUPOS DE RIESGO
    'JQ28' => 'MAYORES DE 60A',
    'JR28' => 'GESTANTES',
    'JS28' => 'PUERPERAS',
    'JT28' => 'PERSONAL DE SALUD',
    'JZ28' => 'ESTUDIANTES',
    'KE28' => 'COMUNIDADES NATIVAS',
    'KG28' => 'PERSONA CON DISCAPACIDAD',
    'KH28' => 'OTROS',
];

// La Seccion K (ANTIAMARILICA EN POBLACION NO VACUNADA Y VIAJEROS A ZONAS
// ENDEMICAS, id_seccion = 10) tiene layout "total_uno" (al igual que la
// seccion H): cada grupo de edad tiene una unica linea en ESNI_LINEA_REPORTE
// cuyo valor es directamente el total de dosis aplicadas para ese grupo
// (columna "Total" del reporte ESNI, sin desglose por dosis). Por eso se
// indexa por etiqueta normalizada (mismo patron que A/B/C/H) y se recupera
// con esniGetCasosPlano(). Las celdas MD28, MF28, MH28, MJ28 y ML28 son
// formulas =MC28, =ME28, =MG28, =MI28 y =MK28 propias de la plantilla.
$cellMapK = [
    // GRUPO DE EDAD / RIESGO "05 a 11 años" (Total)
    'MC28' => '05 a 11 años',
    // GRUPO DE EDAD / RIESGO "12 a 17 años" (Total)
    'ME28' => '12 a 17 años',
    // GRUPO DE EDAD / RIESGO "18 a 29 años" (Total)
    'MG28' => '18 a 29 años',
    // GRUPO DE EDAD / RIESGO "30 a 59 años" (Total)
    'MI28' => '30 a 59 años',
    // GRUPO DE EDAD / RIESGO "60 + años" (Total)
    'MK28' => '60 + años',
];

// La Seccion L (SOLO GESTANTES (dtpa), id_seccion = 18) tiene layout
// "total_uno" (al igual que las secciones H y K): cada grupo de edad tiene
// una unica linea en ESNI_LINEA_REPORTE cuyo valor es directamente el total
// de dosis aplicadas para ese grupo (columna "Total" del reporte ESNI, sin
// desglose por dosis). Por eso se indexa por etiqueta normalizada (mismo
// patron que A/B/C/H/K) y se recupera con esniGetCasosPlano().
$cellMapL = [
    // GRUPO DE EDAD / RIESGO "12 a 17 años" (Total)
    'MM28' => '12 a 17 años',
    // GRUPO DE EDAD / RIESGO "18 a 29 años" (Total)
    'MN28' => '18 a 29 años',
    // GRUPO DE EDAD / RIESGO "30 a 49 años" (Total)
    'MO28' => '30 a 49 años',
];

// La Seccion O (NEUMOCOCO EN POBLACION EN RIESGO, id_seccion = 13) tiene
// layout "total_uno" (al igual que las secciones H, K y L): cada grupo de
// edad / riesgo tiene una unica linea en ESNI_LINEA_REPORTE cuyo valor es
// directamente el total de dosis aplicadas para ese grupo (columna "Total"
// del reporte ESNI, sin desglose por dosis). Por eso se indexa por etiqueta
// normalizada (mismo patron que A/B/C/H/K/L) y se recupera con
// esniGetCasosPlano(). El orden de las celdas sigue el layout horizontal de
// la plantilla Operacional.xlsx (fila 28); las celdas OG28 y OH28 no forman
// parte del mapeo y OR28 = SUM(OD28:OQ28) es formula propia de la plantilla
// (total de la Seccion O).
$cellMapO = [
    // --- CON COMORBILIDAD ---
    'OD28' => 'CON COMORBILIDAD 5-11a',
    'OJ28' => 'CON COMORBILIDAD 12-17a',
    'OK28' => 'CON COMORBILIDAD 18-29a',
    'OL28' => 'CON COMORBILIDAD 30-49a',
    'OM28' => 'CON COMORBILIDAD 50-59a',
    // --- SIN COMORBILIDAD ---
    'ON28' => 'SIN COMORBILIDAD 05-11a',
    'OO28' => 'SIN COMORBILIDAD 12-17a',
    'OP28' => 'SIN COMORBILIDAD 18-29a',
    'OQ28' => 'SIN COMORBILIDAD 30-49a',
    'OE28' => 'SIN COMORBILIDAD 50-59a',
    // --- OTROS GRUPOS DE RIESGO ---
    'OF28' => '60 A MAS AÑOS',
    'OI28' => 'PERSONAL DE SALUD',
];

// La Seccion P (DT-DOSIS ADICIONALES, id_seccion = 21) tiene layout
// "total_uno" (al igual que las secciones H, K, L y O): cada grupo de edad
// tiene una unica linea en ESNI_LINEA_REPORTE cuyo valor es directamente
// el total de dosis aplicadas para ese grupo (columna "Total" del reporte
// ESNI, sin desglose por dosis). Por eso se indexa por etiqueta
// normalizada (mismo patron que A/B/C/H/K/L/O) y se recupera con
// esniGetCasosPlano(). OX28 = SUM(OS28:OW28) es formula propia de la
// plantilla (total de la Seccion P).
$cellMapP = [
    // GRUPO DE EDAD "0 A 11 años" (Total)
    'OS28' => '0 A 11 años',
    // GRUPO DE EDAD "12 a 17 años" (Total)
    'OT28' => '12 a 17 años',
    // GRUPO DE EDAD "18 a 29 años" (Total)
    'OU28' => '18 a 29 años',
    // GRUPO DE EDAD "30 a 59 años" (Total)
    'OV28' => '30 a 59 años',
    // GRUPO DE EDAD "60 a mas años" (Total)
    'OW28' => '60 a mas años',
];

// La Seccion Q (VARICELA) tiene layout "total_uno" (al igual que las
// secciones H, K, L, O y P): cada grupo de edad / riesgo tiene una unica
// linea en ESNI_LINEA_REPORTE cuyo valor es directamente el total de dosis
// aplicadas para ese grupo (columna "Total" del reporte ESNI, sin desglose
// por dosis; la varicela es dosis unica). Por eso se indexa por etiqueta
// normalizada (mismo patron que A/B/C/H/K/L/O/P) y se recupera con
// esniGetCasosPlano(). Las celdas siguen el layout horizontal de la fila 28
// de la plantilla Operacional.xlsx (paso de 4 columnas entre cada celda de
// datos: PG, PK, PO, PS, PW); las columnas intermedias corresponden al
// formato propio de la plantilla.
$cellMapQ = [
    // GRUPO DE EDAD / RIESGO "3 años" (Total)
    'PG28' => '3 años',
    // GRUPO DE EDAD / RIESGO "4 años" (Total)
    'PK28' => '4 años',
    // GRUPO DE EDAD / RIESGO "5 años" (Total)
    'PO28' => '5 años',
    // GRUPO DE EDAD / RIESGO "6 años a mas" (Total)
    'PS28' => '6 años a mas',
    // GRUPO "Personal de Salud" (Total)
    'PW28' => 'Personal de Salud',
];

// La Seccion R (HEPATITIS A, id_seccion = 19) tiene layout "total_uno" (al
// igual que las secciones H, K, L, O, P y Q): cada grupo de edad / riesgo
// tiene una unica linea en ESNI_LINEA_REPORTE cuyo valor es directamente
// el total de dosis aplicadas para ese grupo (columna "Total" del reporte
// ESNI, sin desglose por dosis; la hepatitis A es dosis unica). Por eso se
// indexa por etiqueta normalizada (mismo patron que A/B/C/H/K/L/O/P/Q) y
// se recupera con esniGetCasosPlano(). Las etiquetas "2 AÑOS", "3 AÑOS" y
// "4 AÑOS" corresponden a las lineas de ESNI_LINEA_REPORTE de la seccion R
// (id_linea 197, 198 y 199). Las celdas siguen el layout horizontal de la
// fila 28 de la plantilla Operacional.xlsx (columnas consecutivas TP, TQ,
// TR).
$cellMapR = [
    // GRUPO DE EDAD / RIESGO "2 AÑOS" (Total)
    'TP28' => '2 AÑOS',
    // GRUPO DE EDAD / RIESGO "3 AÑOS" (Total)
    'TQ28' => '3 AÑOS',
    // GRUPO DE EDAD / RIESGO "4 AÑOS" (Total)
    'TR28' => '4 AÑOS',
];

// La Seccion T (SPR-SARAMPION, id_seccion = 20) tiene layout "total_uno"
// (al igual que las secciones H, K, L, O, P, Q y R): cada grupo de edad /
// riesgo tiene una unica linea en ESNI_LINEA_REPORTE cuyo valor es
// directamente el total de dosis aplicadas para ese grupo (columna "Total"
// del reporte ESNI, sin desglose por dosis; la SPR es dosis unica). Por eso
// se indexa por etiqueta normalizada (mismo patron que
// A/B/C/H/K/L/O/P/Q/R) y se recupera con esniGetCasosPlano(). Las etiquetas
// "5 a 10 años", "11 a 59 años" y "Trabajador de Salud" corresponden a las
// lineas de ESNI_LINEA_REPORTE de la seccion T (id_linea 201, 202 y 203).
// Las celdas siguen el layout horizontal de la fila 28 de la plantilla
// Operacional.xlsx (columnas consecutivas UA, UB, UC).
$cellMapT = [
    // GRUPO DE EDAD / RIESGO "5 a 10 años" (Total)
    'UA28' => '5 a 10 años',
    // GRUPO DE EDAD / RIESGO "11 a 59 años" (Total)
    'UB28' => '11 a 59 años',
    // GRUPO DE EDAD / RIESGO "Trabajador de Salud" (Total)
    'UC28' => 'Trabajador de Salud',
];

// La Seccion J (HEPATITIS B EN POBLACION DE 05 A 59 ANIOS) tiene layout
// "matriz_dosis": las 3 lineas (D1/D2/D3) de cada grupo de edad comparten la
// MISMA etiqueta en ESNI_LINEA_REPORTE, por eso cada celda se mapea al par
// [etiqueta, dosis_codigo] y se resuelve con esniGetCasosJDosisPlano() sobre
// el indice compuesto $casosPorEtiquetaDosisJ (clave etiqueta|dosis).
$cellMapJ = [
    // GRUPO DE EDAD "05 y 11 años" (etiqueta en BD: "05 a 11 años")
    // D1 / D2 / D3
    'KW28' => ['05 a 11 años', 'D1'],
    'KX28' => ['05 a 11 años', 'D2'],
    'KY28' => ['05 a 11 años', 'D3'],
    // GRUPO DE EDAD "12 y 17 años" (etiqueta en BD: "12 a 17 años")
    // D1 / D2 / D3
    'LA28' => ['12 a 17 años', 'D1'],
    'LB28' => ['12 a 17 años', 'D2'],
    'LC28' => ['12 a 17 años', 'D3'],
    // GRUPO DE EDAD "18 y 29 años" (etiqueta en BD: "18 a 29 años")
    // D1 / D2 / D3
    'LE28' => ['18 a 29 años', 'D1'],
    'LF28' => ['18 a 29 años', 'D2'],
    'LG28' => ['18 a 29 años', 'D3'],
    // GRUPO DE EDAD "30 y 59 años" (etiqueta en BD: "30 a 59 años")
    // D1 / D2 / D3
    'LI28' => ['30 a 59 años', 'D1'],
    'LJ28' => ['30 a 59 años', 'D2'],
    'LK28' => ['30 a 59 años', 'D3'],
    // GRUPO "Personal de Salud"
    // D1 / D2 / D3
    'LQ28' => ['Personal de Salud', 'D1'],
    'LR28' => ['Personal de Salud', 'D2'],
    'LS28' => ['Personal de Salud', 'D3'],
    // GRUPO "Gestantes" (sub-zona J.1 HEPATITIS / PERSONAS DE RIESGO)
    // D1 / D2 / D3
    'TM28' => ['Gestantes', 'D1'],
    'TN28' => ['Gestantes', 'D2'],
    'TO28' => ['Gestantes', 'D3'],
];

// La Seccion N (VACUNA VPH) tiene layout "matriz_sexo": por cada grupo de
// edad existen DOS lineas con la MISMA etiqueta en ESNI_LINEA_REPORTE que se
// distinguen unicamente por el campo sexo ('M' = Masculino, 'F' = Femenino),
// por eso cada celda se mapea al par [etiqueta, sexo] y se resuelve con
// esniGetCasosNSexoPlano() sobre el indice compuesto $casosPorEtiquetaSexoN
// (clave etiqueta|sexo).
$cellMapN = [
    // GRUPO DE EDAD / RIESGO "9 años" (Masculino / Femenino)
    'QF28' => ['9 años', 'M'],
    'QG28' => ['9 años', 'F'],
    // GRUPO DE EDAD / RIESGO "10 años" (Masculino / Femenino)
    'QM28' => ['10 años', 'M'],
    'QN28' => ['10 años', 'F'],
    // GRUPO DE EDAD / RIESGO "11 años" (Masculino / Femenino)
    'QT28' => ['11 años', 'M'],
    'QU28' => ['11 años', 'F'],
    // GRUPO DE EDAD / RIESGO "12 años" (Masculino / Femenino)
    'RA28' => ['12 años', 'M'],
    'RB28' => ['12 años', 'F'],
    // GRUPO DE EDAD / RIESGO "13 años" (Masculino / Femenino)
    'RH28' => ['13 años', 'M'],
    'RI28' => ['13 años', 'F'],
    // GRUPO DE EDAD / RIESGO "14 a mas" (Masculino / Femenino)
    'RO28' => ['14 a mas', 'M'],
    'RP28' => ['14 a mas', 'F'],
];

// Construir el mapa final [celda => valor]
// -----------------------------------------------------------------------------
// Nota sobre tipos: ExcelTemplateFiller decide si escribir el valor como
// numero (<v>11</v>) o como texto (<is><t>Enero</t></is>) en funcion del tipo
// PHP del valor:
//   - int / float             -> numero  (Casos)
//   - string numerica "123"   -> numero  (Casos)
//   - string no numerica      -> texto   (nombres de mes, establecimiento)
// Por eso las celdas B5 y C28 (textos) y las celdas de Casos (numeros) pueden
// convivir en el mismo array $cellValues.
// -----------------------------------------------------------------------------
$cellValues = [
    // === ENCABEZADO (filtros seleccionados por el usuario) ===
    // B5 = Mes seleccionado (nombre legible: Enero, Febrero, ...)
    'B5'  => $nombreMes,
    // C28 = Establecimiento seleccionado (nombre legible)
    'C28' => $nombreEst,
];
foreach ($cellMapA as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaA, $etiqueta);
}
foreach ($cellMapB as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaB, $etiqueta);
}
foreach ($cellMapC as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaC, $etiqueta);
}
foreach ($cellMapD as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaD, $etiqueta);
}
foreach ($cellMapE1 as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaE1, $etiqueta);
}
foreach ($cellMapE2 as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaE2, $etiqueta);
}
foreach ($cellMapF as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaF, $etiqueta);
}
foreach ($cellMapF2 as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaF2, $etiqueta);
}
foreach ($cellMapG as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaG, $etiqueta);
}
foreach ($cellMapH as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaH, $etiqueta);
}
foreach ($cellMapK as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaK, $etiqueta);
}
foreach ($cellMapL as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaL, $etiqueta);
}
foreach ($cellMapO as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaO, $etiqueta);
}
foreach ($cellMapP as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaP, $etiqueta);
}
foreach ($cellMapQ as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaQ, $etiqueta);
}
foreach ($cellMapR as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaR, $etiqueta);
}
foreach ($cellMapT as $cellRef => $etiqueta) {
    $cellValues[$cellRef] = esniGetCasosPlano($casosPorEtiquetaT, $etiqueta);
}
foreach ($cellMapJ as $cellRef => $parJ) {
    [$etiquetaJ, $dosisJ] = $parJ;
    $cellValues[$cellRef] = esniGetCasosJDosisPlano($casosPorEtiquetaDosisJ, $etiquetaJ, $dosisJ);
}
foreach ($cellMapN as $cellRef => $parN) {
    [$etiquetaN, $sexoN] = $parN;
    $cellValues[$cellRef] = esniGetCasosNSexoPlano($casosPorEtiquetaSexoN, $etiquetaN, $sexoN);
}

// ============================================================================
// 5. Llenar la plantilla y descargar
// ============================================================================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cellValues);

// Nombre del archivo descargable. Incluye anio, mes (si viene) y timestamp
// para evitar colisiones de cache en el navegador.
$nombreArchivo = 'Reporte_Operacional_ESNI_Plano_'
               . ($filtros['anio'] !== '' ? $filtros['anio'] : 'all')
               . ($filtros['mes'] !== '' ? '_' . str_pad($filtros['mes'], 2, '0', STR_PAD_LEFT) : '')
               . '_' . date('Ymd_His')
               . '.xlsx';

try {
    $filler->download($nombreArchivo);
} catch (Throwable $e) {
    http_response_code(500);
    htmlErrorPlano(
        'Error al generar el Excel',
        'No se pudo generar el archivo Excel.<br><br>' .
        '<b>Error tecnico:</b><br>' .
        '<pre style="background:#f8f9fa;padding:.6rem;border-radius:.25rem;overflow:auto;">' .
        htmlspecialchars($e->getMessage()) . '</pre><br>' .
        '<b>Posibles causas:</b><br>' .
        '<ul>' .
        '<li>El directorio <code>uploads/tmp/</code> no existe o no es escribible. Cree la carpeta con permisos 0755 (o 0777).</li>' .
        '<li>La plantilla <code>uploads/Operacional.xlsx</code> esta corrupta o no es un .xlsx valido.</li>' .
        '<li>El hosting tiene funciones restringidas (tempnam, copy, etc.).</li>' .
        '</ul>'
    );
}
exit;

// ============================================================================
// FUNCIONES AUXILIARES (locales, para no depender de esni_export.php)
// ============================================================================

/**
 * Indexa las lineas de una seccion del reporte ESNI por etiqueta normalizada.
 *
 * Recorre $reporte['secciones'] buscando la seccion cuyo codigo coincida
 * (comparacion case-insensitive) con $codigoSeccion y devuelve un mapa
 * [etiqueta_normalizada => cantidad]. Si la etiqueta ya existe (no deberia),
 * suma las cantidades para ser tolerante con configuraciones que registren
 * la misma vacuna en varias lineas con la misma etiqueta.
 *
 * @param array  $reporte        Resultado de esniEjecutarReporte().
 * @param string $codigoSeccion Codigo de la seccion a indexar ('A', 'B', ...).
 * @return array Mapa [etiqueta_normalizada => cantidad].
 */
function esniIndexarCasosSeccionPlano(array $reporte, string $codigoSeccion): array
{
    $casosPorEtiqueta = [];
    foreach ($reporte['secciones'] as $sec) {
        if (strcasecmp($sec['codigo'], $codigoSeccion) !== 0) {
            continue;
        }
        foreach ($sec['lineas'] as $lin) {
            $etqNorm = esniNormalizarEtiquetaPlano($lin['etiqueta']);
            // Si la etiqueta ya existe (no deberia), sumamos las cantidades
            // para ser tolerantes con configuraciones que registren la misma
            // vacuna en varias lineas con la misma etiqueta.
            if (isset($casosPorEtiqueta[$etqNorm])) {
                $casosPorEtiqueta[$etqNorm] += (int)$lin['cantidad'];
            } else {
                $casosPorEtiqueta[$etqNorm] = (int)$lin['cantidad'];
            }
        }
        break; // Solo existe una seccion con ese codigo
    }
    return $casosPorEtiqueta;
}

/**
 * Devuelve la cantidad de casos para una etiqueta normalizada dada.
 *
 * @param array  $casosPorEtiqueta Mapa [etiqueta_normalizada => cantidad].
 * @param string $etiqueta         Etiqueta tal como viene en la config ESNI.
 * @return int Cantidad de casos (0 si no hay linea con esa etiqueta).
 */
function esniGetCasosPlano(array $casosPorEtiqueta, string $etiqueta): int
{
    $etqNorm = esniNormalizarEtiquetaPlano($etiqueta);
    return $casosPorEtiqueta[$etqNorm] ?? 0;
}

/**
 * Devuelve la cantidad de casos para una etiqueta + dosis de la seccion J
 * (Hepatitis B en poblacion de 05 a 59 anios). Necesario porque en la
 * seccion J las 3 lineas de cada grupo de edad (D1/D2/D3) comparten la misma
 * etiqueta; el indexado se hace por la clave compuesta
 * "etiqueta_normalizada|dosis_codigo".
 *
 * @param array  $casosPorEtiquetaDosis Mapa [etqNorm . '|' . dosis_codigo => int].
 * @param string $etiqueta              Etiqueta de la linea en ESNI_LINEA_REPORTE.
 * @param string $dosisCodigo           Codigo de dosis (D1, D2, D3, ...).
 * @return int Cantidad de casos (0 si no hay linea con esa etiqueta+dosis).
 */
function esniGetCasosJDosisPlano(array $casosPorEtiquetaDosis, string $etiqueta, string $dosisCodigo): int
{
    $etqNorm  = esniNormalizarEtiquetaPlano($etiqueta);
    $dosisCod = strtoupper(trim($dosisCodigo));
    $key      = $etqNorm . '|' . $dosisCod;
    return $casosPorEtiquetaDosis[$key] ?? 0;
}

/**
 * Devuelve la cantidad de casos para una etiqueta + sexo de la seccion N
 * (VACUNA VPH: femenino y masculino, dosis unica). Necesario porque en la
 * seccion N las 2 lineas de cada grupo de edad (Masculino/Femenino) comparten
 * la misma etiqueta; el indexado se hace por la clave compuesta
 * "etiqueta_normalizada|sexo".
 *
 * @param array  $casosPorEtiquetaSexo Mapa [etqNorm . '|' . sexo => int].
 * @param string $etiqueta             Etiqueta de la linea en ESNI_LINEA_REPORTE.
 * @param string $sexo                 Sexo de la linea ('M' = Masculino, 'F' = Femenino).
 * @return int Cantidad de casos (0 si no hay linea con esa etiqueta+sexo).
 */
function esniGetCasosNSexoPlano(array $casosPorEtiquetaSexo, string $etiqueta, string $sexo): int
{
    $etqNorm = esniNormalizarEtiquetaPlano($etiqueta);
    $sexoCod = strtoupper(trim($sexo));
    $key     = $etqNorm . '|' . $sexoCod;
    return $casosPorEtiquetaSexo[$key] ?? 0;
}

/**
 * Normaliza una etiqueta de linea para comparacion robusta.
 *   - MAYUSCULAS (preserva acentos)
 *   - Colapsa espacios multiples a uno solo
 *   - Quita espacios al inicio/final
 *   - Quita asterisco inicial "*" (marcador de "linea informativa")
 *
 * @param string $etiqueta
 * @return string
 */
function esniNormalizarEtiquetaPlano(string $etiqueta): string
{
    $s = trim($etiqueta);
    // Quitar asterisco inicial si lo hay (lineas informativas como "* Personal de Salud")
    $s = preg_replace('/^\*\s*/', '', $s);
    // PASAR A MAYUSCULAS (preserva acentos)
    $s = mb_strtoupper($s, 'UTF-8');
    // Colapsar espacios multiples
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

/**
 * Muestra un error HTML amigable y termina la ejecucion.
 *
 * @param string $titulo  Titulo corto del error.
 * @param string $mensaje Mensaje HTML con detalles y soluciones.
 */
function htmlErrorPlano(string $titulo, string $mensaje): void
{
    // Asegurar que no haya salida previa que rompa el HTML
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<title>Error - ' . htmlspecialchars($titulo) . '</title>'
       . '<style>'
       . 'body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:20px;color:#333;}'
       . '.container{max-width:720px;margin:40px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);}'
       . 'h1{color:#dc3545;margin:0 0 16px 0;font-size:1.5rem;}'
       . '.icon{font-size:48px;color:#dc3545;margin-bottom:16px;}'
       . 'pre{font-family:Consolas,monospace;font-size:.85rem;}'
       . 'a{color:#0d6efd;}'
       . '</style></head><body>'
       . '<div class="container">'
       . '<div class="icon">&#9888;</div>'
       . '<h1>' . htmlspecialchars($titulo) . '</h1>'
       . '<div style="line-height:1.6;">' . $mensaje . '</div>'
       . '<hr style="margin:24px 0;border:none;border-top:1px solid #eee;">'
       . '<p style="font-size:.85rem;color:#6c757d;margin:0;">'
       . 'Sistema de Gestion de Datos HIS - Modulo ESNI (Exportar Plano)</p>'
       . '</div></body></html>';
    exit;
}
